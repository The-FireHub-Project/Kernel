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

use FireHub\Core\Boundary\Kernel\ {
    Bootstrapper, Terminator
};
use FireHub\Runtime;
use Throwable;

/**
 * ### Registers and executes kernel lifecycle components
 *
 * Executes bootstrappers in registration order and terminators in reverse order.
 * Shutdown attempts every terminator even if an earlier terminator fails.
 *
 * State validation belongs to the lifecycle controller, not this execution manager.
 * @since 1.0.0
 */
final class Manager {

    /**
     * ### Registered bootstrappers
     * @since 1.0.0
     *
     * @var list<\FireHub\Core\Boundary\Kernel\Bootstrapper>
     */
    private array $bootstrappers = [];

    /**
     * ### Registered terminators
     * @since 1.0.0
     *
     * @var list<\FireHub\Core\Boundary\Kernel\Terminator>
     */
    private array $terminators = [];

    /**
     * ### Registers a bootstrapper
     * @since 1.0.0
     *
     * @param \FireHub\Core\Boundary\Kernel\Bootstrapper $bootstrapper <p>
     * Bootstrapper to register.
     * </p>
     *
     * @return void
     */
    public function registerBootstrapper (Bootstrapper $bootstrapper):void {

        $this->bootstrappers[] = $bootstrapper;

    }

    /**
     * ### Registers a terminator
     * @since 1.0.0
     *
     * @param \FireHub\Core\Boundary\Kernel\Terminator $terminator <p>
     * Terminator to register.
     * </p>
     *
     * @return void
     */
    public function registerTerminator (Terminator $terminator):void {

        $this->terminators[] = $terminator;

    }

    /**
     * ### Initializes registered components
     *
     * Stops immediately if a bootstrapper fails, preserving its original exception.
     * @since 1.0.0
     *
     * @uses \FireHub\Core\Boundary\Kernel\Bootstrapper::bootstrap() To bootstrap component.
     *
     * @return void
     */
    public function initialize ():void {

        foreach ($this->bootstrappers as $bootstrapper)
            $bootstrapper->bootstrap();

    }

    /**
     * ### Terminates registered components
     *
     * Attempts every terminator in reverse registration order and rethrows the
     * first failure after all terminators have been attempted.
     * @since 1.0.0
     *
     * @uses \FireHub\Core\Boundary\Kernel\Terminator::terminate() To terminate component.
     * @uses \FireHub\Runtime\Arr\Transform::reverse() Reverses an array.
     *
     * @throws Throwable If any terminator fails to terminate.
     *
     * @return void
     */
    public function terminate ():void {

        $failure = null;

        foreach (Runtime\Arr\Transform::reverse($this->terminators) as $terminator) {

            try {

                $terminator->terminate();

            } catch (Throwable $exception) {

                $failure ??= $exception;

            }

        }

        if ($failure !== null) throw $failure;

    }

}