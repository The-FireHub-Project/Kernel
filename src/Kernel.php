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

use FireHub\Core\Boundary\Kernel as KernelBoundary;
use FireHub\Core\Meta\Enum\Kernel\State;
use FireHub\Kernel\Lifecycle\Controller;
use FireHub\Kernel\Lifecycle\Dependency\Boundary;

/**
 * ### Manages the kernel lifecycle
 *
 * Implements the kernel contract and coordinates runtime initialization and termination.
 *
 * Supports registration of bootstrappers and terminators, which are executed according to their configured
 * execution order, with lower order values executed first.
 *
 * Lifecycle components can only be registered before kernel initialization begins.
 *
 * Repeated boot calls on a booted kernel and repeated shutdown calls on a terminated kernel are no-ops.
 * A failed kernel cannot be rebooted, but shutdown can be attempted to release resources.
 *
 * <code>
 * use FireHub\Kernel\Kernel;
 *
 * $kernel = new Kernel;
 *
 * $kernel->registerBootstrapper(new ClassThatImplementsBootstrapper);
 * $kernel->registerTerminator(new ClassThatImplementsTerminator);
 *
 * $kernel->boot();
 *
 * // write code here
 *
 * $kernel->shutdown();
 * </code>
*
 * @since 1.0.0
 */
final readonly class Kernel implements KernelBoundary {

    /**
     * ### Kernel lifecycle controller
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
     * ### Gets the kernel lifecycle dependency boundary
     *
     * Exposes the kernel as a parent lifecycle for dependent runtime components.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::dependencyBoundary() To get the dependency boundary.
     *
     * @return \FireHub\Kernel\Lifecycle\Dependency\Boundary Kernel lifecycle dependency boundary.
     */
    public function dependencyBoundary ():Boundary {

        return $this->lifecycle->dependencyBoundary();

    }

    /**
     * ### Registers a kernel bootstrapper
     *
     * <code>
     * use FireHub\Kernel\Kernel;
     *
     * $kernel = new Kernel;
     *
     * $kernel->registerBootstrapper(new ClassThatImplementsBootstrapper);
     * </code>
     *
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::registerBootstrapper() To register a bootstrapper.
     *
     * @param \FireHub\Kernel\Bootstrapper $bootstrapper <p>
     * Bootstrapper to register.
     * </p>
     * @param int $order [optional] <p>
     * Execution order.
     * </p>
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If registration is not allowed.
     *
     * @return void
     */
    public function registerBootstrapper (Bootstrapper $bootstrapper, int $order = 0):void {

        $this->lifecycle->registerBootstrapper($bootstrapper, $order);

    }

    /**
     * ### Registers a kernel terminator
     *
     * <code>
     * use FireHub\Kernel\Kernel;
     *
     * $kernel = new Kernel;
     *
     * $kernel->registerTerminator(new ClassThatImplementsTerminator);
     * </code>
     *
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::registerTerminator() To register a terminator.
     *
     * @param \FireHub\Kernel\Terminator $terminator <p>
     * Terminator to register.
     * </p>
     * @param int $order [optional] <p>
     * Execution order.
     * </p>
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If registration is not allowed.
     *
     * @return void
     */
    public function registerTerminator (Terminator $terminator, int $order = 0):void {

        $this->lifecycle->registerTerminator($terminator, $order);

    }

    /**
     * {@inheritDoc}
     *
     * <code>
     * use FireHub\Kernel\Kernel;
     *
     * $kernel = new Kernel;
     *
     * $kernel->boot();
     * </code>
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
     * {@inheritDoc}
     *
     * <code>
     * use FireHub\Kernel\Kernel;
     *
     * $kernel = new Kernel;
     *
     * $kernel->boot();
     *
     * $kernel->shutdown();
     * </code>
     *
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::shutdown() To shut down the kernel.
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If shutdown is not allowed.
     * @throws \FireHub\Kernel\Exception\KernelShutdownException If termination fails.
     */
    public function shutdown ():void {

        $this->lifecycle->shutdown();

    }

    /**
     * {@inheritDoc}
     *
     * <code>
     * use FireHub\Kernel\Kernel;
     *
     * $kernel = new Kernel;
     *
     * $kernel->state();
     *
     * // FireHub\Core\Meta\Enum\Kernel\State::CREATED
     *
     * $kernel->boot();
     * $kernel->state();
     *
     * // FireHub\Core\Meta\Enum\Kernel\State::BOOTED
     *
     * $kernel->shutdown();
     * $kernel->state();
     *
     * // FireHub\Core\Meta\Enum\Kernel\State::TERMINATED
     * </code>
     *
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::state() To get the current state.
     */
    public function state ():State {

        return $this->lifecycle->state();

    }

}