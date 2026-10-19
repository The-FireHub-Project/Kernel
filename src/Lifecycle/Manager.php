<?php declare(strict_types = 1);

/**
 * This file is part of the FireHub Project ecosystem
 *
 * @author Danijel Galić <danijel.galic@outlook.com>
 * @copyright 2026-present The FireHub Project - All rights reserved
 * @license https://opensource.org/license/Apache-2-0 Apache License, Version 2.0
 *
 * @php-version >=8.1
 * @package Kernel
 */

namespace FireHub\Kernel\Lifecycle;

use FireHub\Runtime;
use Closure, Throwable;

/**
 * ### Manages lifecycle component execution
 *
 * Registers startup and shutdown callbacks and execute them according to their configured execution order.
 *
 * Components with lower order values execute first. Components with equal order values retain their original
 * registration order.
 *
 * Startup execution stops immediately when a component fails.
 *
 * Shutdown execution attempts every registered component even when one or more parts fail. The first captured
 * failure is rethrown after all shutdown callbacks have been attempted.
 *
 * This manager does not maintain lifecycle states or enforce lifecycle transitions. These responsibilities belong to
 * the lifecycle engine.
 * @since 1.0.0
 *
 * @internal
 */
final class Manager {

    /**
     * ### Registered startup callbacks
     * @since 1.0.0
     *
     * @var list<array{callback: Closure():void, order: int, index: int}>
     */
    private array $startup = [];

    /**
     * ### Registered shutdown callbacks
     * @since 1.0.0
     *
     * @var list<array{callback: Closure():void, order: int, index: int}>
     */
    private array $shutdown = [];

    /**
     * ### Registration sequence
     *
     * Preserves deterministic execution order for components having identical order values.
     * @since 1.0.0
     */
    private int $sequence = 0;

    /**
     * ### Registers a startup callback
     *
     * Adds a callback to the startup execution sequence.
     *
     * Lower order values execute before higher order values. Callbacks have equal order values executed in their
     * registration order.
     * @since 1.0.0
     *
     * @param Closure():void $callback <p>
     * Startup callback.
     * </p>
     * @param int $order <p>
     * Execution order.
     * </p>
     *
     * @return void
     */
    public function registerStartup (Closure $callback, int $order):void {

        $this->startup[] = [
            'callback' => $callback,
            'order' => $order,
            'index' => $this->sequence++
        ];

    }

    /**
     * ### Registers a shutdown callback
     *
     * Adds a callback to the shutdown execution sequence.
     *
     * Lower order values execute before higher order values. Callbacks have equal order values executed in their
     * registration order.
     * @since 1.0.0
     *
     * @param Closure():void $callback <p>
     * Shutdown callback.
     * </p>
     * @param int $order <p>
     * Execution order.
     * </p>
     *
     * @return void
     */
    public function registerShutdown (Closure $callback, int $order):void {

        $this->shutdown[] = [
            'callback' => $callback,
            'order' => $order,
            'index' => $this->sequence++
        ];

    }

    /**
     * ### Executes startup callbacks
     *
     * Executes all registered startup callbacks in ascending order.
     *
     * Execution stops immediately when a callback throws an exception. The original exception is propagated to the
     * caller.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Manager::ordered() To order callbacks.
     *
     * @throws Throwable If a startup callback fails.
     *
     * @return void
     */
    public function start ():void {

        foreach ($this->ordered($this->startup) as $entry)
            ($entry['callback'])();

    }

    /**
     * ### Executes shutdown callbacks
     *
     * Executes all registered shutdown callbacks in ascending order.
     *
     * If a callback fails, execution continues with the remaining callbacks. The first captured failure is rethrown
     * after every registered callback has been attempted.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Manager::ordered() To order callbacks.
     *
     * @throws Throwable If a shutdown callback fails.
     *
     * @return void
     */
    public function stop ():void {

        $failure = null;

        foreach ($this->ordered($this->shutdown) as $entry) {

            try {

                ($entry['callback'])();

            } catch (Throwable $exception) {

                $failure ??= $exception;

            }

        }

        if ($failure !== null) throw $failure;

    }

    /**
     * ### Orders lifecycle callbacks
     *
     * Sorts callbacks by their configured execution order and uses their registration index to preserve
     * deterministic ordering when priorities are equal.
     * @since 1.0.0
     *
     * @uses \FireHub\Runtime\Arr\Ordering::sortBy() To sort callbacks.
     *
     * @param list<array{callback: Closure():void, order: int, index: int}> $entries
     * Lifecycle callbacks.
     *
     * @return list<array{callback: Closure():void, order: int, index: int}> Ordered lifecycle callbacks.
     */
    private function ordered (array $entries):array {

        Runtime\Arr\Ordering::sortBy(
            $entries,
            static fn (array $a, array $b):int => (
                $a['order'] <=> $b['order']) ?: ($a['index'] <=> $b['index']
            )
        );

        return $entries;

    }

}