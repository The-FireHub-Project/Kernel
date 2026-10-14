<?php declare(strict_types = 1);

/**
 * This file is part of the FireHub Project ecosystem
 *
 * @author Danijel Galić <danijel.galic@outlook.com>
 * @copyright 2026-present The FireHub Project - All rights reserved
 * @license https://opensource.org/license/Apache-2-0 Apache License, Version 2.0
 *
 * @php-version >=8.5
 * @package Kernel
 */

namespace FireHub\Kernel;

use FireHub\Core\Boundary\Kernel\Application as ApplicationBoundary;
use FireHub\Core\Meta\Enum\Kernel\State;
use FireHub\Kernel\Application\ {
    Bootstrapper as ApplicationBootstrapper, Terminator as ApplicationTerminator,
};
use FireHub\Kernel\Lifecycle\Controller;

/**
 * ### Manages the application kernel lifecycle
 *
 * Implements the application kernel contract and coordinates application initialization and termination.
 *
 * Supports global and application-specific bootstrappers and terminators. Bootstrappers are executed
 * in registration order, while terminators are executed in reverse registration order.
 *
 * Lifecycle components can only be registered before kernel initialization begins.
 *
 * Repeated boot calls on a booted kernel and repeated shutdown calls on a terminated kernel are no-ops.
 * A failed kernel cannot be rebooted, but shutdown can be attempted to release resources.
 * @since 1.0.0
 */
final readonly class Application implements ApplicationBoundary {

    /**
     * ### Application lifecycle controller
     * @since 1.0.0
     */
    private Controller $lifecycle;

    /**
     * ### Constructor
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct () {

        $this->lifecycle = new Controller;

    }

    /**
     * ### Registers a global or application-specific bootstrapper
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::registerBootstrapper() To register a bootstrapper.
     *
     * @param \FireHub\Kernel\Bootstrapper|\FireHub\Kernel\Application\Bootstrapper $bootstrapper <p>
     * Bootstrapper to register.
     * </p>
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If registration is not allowed.
     *
     * @return void
     */
    public function registerBootstrapper (Bootstrapper|ApplicationBootstrapper $bootstrapper):void {

        $this->lifecycle->registerBootstrapper($bootstrapper);

    }

    /**
     * ### Registers a global or application-specific terminator
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::registerTerminator() To register a terminator.
     *
     * @param \FireHub\Kernel\Terminator|\FireHub\Kernel\Application\Terminator $terminator <p>
     * Terminator to register.
     * </p>
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If registration is not allowed.
     *
     * @return void
     */
    public function registerTerminator (Terminator|ApplicationTerminator $terminator):void {

        $this->lifecycle->registerTerminator($terminator);

    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::boot() To boot the kernel.
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If booting is not allowed.
     * @throws \FireHub\Kernel\Exception\KernelBootException If initialization fails.
     */
    public function boot ():void {

        $this->lifecycle->boot();

    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::shutdown() To shutdown the kernel.
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If shutdown is not allowed.
     * @throws \FireHub\Kernel\Exception\KernelShutdownException If termination fails.
     */
    public function shutdown ():void {

        $this->lifecycle->shutdown();

    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::state() To get the current state.
     */
    public function state ():State {

        return $this->lifecycle->state();

    }

}