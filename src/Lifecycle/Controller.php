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

namespace FireHub\Kernel\Lifecycle;

use FireHub\Core\Boundary\Kernel;
use FireHub\Core\Boundary\Kernel\ {
    Bootstrapper, Terminator
};
use FireHub\Core\Meta\Enum\Kernel\State;
use FireHub\Kernel\Exception\ {
    InvalidKernelStateException, KernelBootException, KernelShutdownException
};
use FireHub\Kernel\Lifecycle\Exception\ {
    InvalidLifecycleStateException, LifecycleExecutionException
};

/**
 * ### Coordinates the kernel lifecycle
 *
 * Adapts the generic lifecycle engine to the kernel's initialization and termination contracts.
 *
 * Maintains an independent lifecycle engine configured with kernel-specific states and exposes kernel-specific
 * exceptions to callers.
 *
 * Bootstrappers and terminators are registered as callbacks without changing their public contracts.
 *
 * The controller does not implement lifecycle state transitions directly. All state management and component
 * execution are delegated to the engine.
 * @since 1.0.0
 */
final readonly class Controller implements Kernel {

    /**
     * ### Kernel lifecycle engine
     * @since 1.0.0
     *
     * @var \FireHub\Kernel\Lifecycle\Engine<State>
     */
    private Engine $lifecycle;

    /**
     * ### Constructor
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct () {

        $this->lifecycle = new Engine(
            new States(
                State::CREATED,
                State::BOOTING,
                State::BOOTED,
                State::SHUTTING_DOWN,
                State::TERMINATED,
                State::BOOT_FAILED,
                State::SHUTDOWN_FAILED
            )
        );

    }

    /**
     * ### Registers a kernel bootstrapper
     *
     * Registers a bootstrapper for execution during kernel initialization.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Engine::registerStartup() To register a bootstrapper.
     * @uses \FireHub\Kernel\Bootstrapper::bootstrap() To execute the bootstrapper.
     *
     * @param \FireHub\Kernel\Bootstrapper $bootstrapper <p>
     * Bootstrapper to register.
     * </p>
     * @param int $order <p>
     * Execution order.
     * </p>
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If registration is not allowed.
     *
     * @return void
     */
    public function registerBootstrapper (Bootstrapper $bootstrapper, int $order):void {

        try {

            $this->lifecycle->registerStartup(
                static function () use ($bootstrapper):void {

                    $bootstrapper->bootstrap();

                },
                $order
            );

        } catch (InvalidLifecycleStateException $exception) {

            throw new InvalidKernelStateException(
                'Cannot register a bootstrapper in the current kernel state.',
                previous: $exception
            );

        }

    }

    /**
     * ### Registers a kernel terminator
     *
     * Registers a terminator for execution during kernel shutdown.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Engine::registerShutdown() To register a terminator.
     * @uses \FireHub\Kernel\Terminator::terminate() To execute the terminator.
     *
     * @param \FireHub\Kernel\Terminator $terminator <p>
     * Terminator to register.
     * </p>
     * @param int $order <p>
     * Execution order.
     * </p>
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If registration is not allowed.
     *
     * @return void
     */
    public function registerTerminator (Terminator $terminator, int $order):void {

        try {

            $this->lifecycle->registerShutdown(
                static function () use ($terminator):void {

                    $terminator->terminate();

                },
                $order
            );

        } catch (InvalidLifecycleStateException $exception) {

            throw new InvalidKernelStateException(
                'Cannot register a terminator in the current kernel state.',
                previous: $exception
            );

        }

    }

    /**
     * ### Boots the kernel
     *
     * Executes registered bootstrappers and transitions the kernel into its booted state when initialization succeeds.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Engine::start() To execute registered bootstrappers.
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If booting is not allowed.
     * @throws \FireHub\Kernel\Exception\KernelBootException If a bootstrapper fails.
     *
     * @return void
     */
    public function boot ():void {

        try {

            $this->lifecycle->start();

        } catch (InvalidLifecycleStateException $exception) {

            throw new InvalidKernelStateException(
                'Cannot boot the kernel in the current state.',
                previous: $exception
            );

        } catch (LifecycleExecutionException $exception) {

            throw new KernelBootException(
                'Failed to boot the kernel.',
                previous: $exception->getPrevious() ?? $exception
            );

        }

    }

    /**
     * ### Shuts down the kernel
     *
     * Executes registered terminators and transitions the kernel into its terminated state when shutdown succeeds.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Engine::stop() To execute registered terminators.
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If shutdown is not allowed.
     * @throws \FireHub\Kernel\Exception\KernelShutdownException If a terminator fails.
     *
     * @return void
     */
    public function shutdown ():void {

        try {

            $this->lifecycle->stop();

        } catch (InvalidLifecycleStateException $exception) {

            throw new InvalidKernelStateException(
                'Cannot shut down the kernel in the current state.',
                previous: $exception
            );

        } catch (LifecycleExecutionException $exception) {

            throw new KernelShutdownException(
                'Failed to shut down the kernel.',
                previous: $exception->getPrevious() ?? $exception
            );

        }

    }

    /**
     * ### Gets the current kernel lifecycle state
     *
     * Returns the kernel's own lifecycle state without conversion.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Engine::state() To get the current state.
     *
     * @return \FireHub\Core\Meta\Enum\Kernel\State Current kernel lifecycle state.
     */
    public function state ():State {

        return $this->lifecycle->state();

    }

}