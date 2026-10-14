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

namespace FireHub\Kernel\Terminator;

use FireHub\Core\Boundary\Kernel\Terminator;
use FireHub\Runtime;
use Throwable;

/**
 * ### Manages the kernel termination sequence
 *
 * Registers and executes termination components, according to their configured execution order.
 *
 * Components with lower order values execute first. Components with equal order values retain their original
 * registration order.
 *
 * Termination continues when individual components fail, allowing remaining components to release their resources.
 * @since 1.0.0
 */
final class Sequence {

    /**
     * ### Registered bootstrappers
     * @since 1.0.0
     *
     * @var list<array{terminator: \FireHub\Core\Boundary\Kernel\Terminator, order: int}>
     */
    private array $terminators = [];

    /**
     * ### Registers a terminator
     * @since 1.0.0
     *
     * @param \FireHub\Core\Boundary\Kernel\Terminator $terminator <p>
     * Termination component.
     * </p>
     * @param int $order <p>
     * Execution order.
     * </p>
     *
     * @return void
     */
    public function register (Terminator $terminator, int $order):void {

        $this->terminators[] = [
            'terminator' => $terminator,
            'order' => $order
        ];

    }

    /**
     * ### Executes the termination sequence
     *
     * Executes terminators in ascending order.
     *
     * Attempts to execute every registered terminator even when one fails. The first captured exception is rethrown
     * after execution completes.
     * @since 1.0.0
     *
     * @uses \FireHub\Runtime\Arr\Ordering::sortBy() Sorts an array by a user-defined comparison function.
     * @uses \FireHub\Core\Boundary\Kernel\Terminator::terminate() To terminate component.
     *
     * @throws Throwable If a terminator fails.
     *
     * @return void
     */
    public function execute ():void {

        $entries = $this->terminators;

        Runtime\Arr\Ordering::sortBy(
            $entries,
            static fn (array $a, array $b):int => $a['order'] <=> $b['order']
        );

        $failure = null;

        foreach ($entries as $entry) {

            try {

                $entry['terminator']->terminate();

            } catch (Throwable $exception) {

                $failure ??= $exception;

            }

        }

        if ($failure !== null) throw $failure;

    }

}