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

namespace FireHub\Kernel;

use FireHub\Core\Boundary\Kernel\Application as ApplicationBoundary;
use FireHub\Core\Meta\Enum\Kernel\State;
use FireHub\Kernel\Application\ {
    Bootstrapper as ApplicationBootstrapper, Terminator as ApplicationTerminator,
};
use FireHub\Kernel\Exception\ {
    InvalidKernelStateException, KernelBootException, KernelShutdownException
};
use FireHub\Runtime;
use Throwable;

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
final class Application implements ApplicationBoundary {

    /**
     * ### Current kernel lifecycle state
     * @since 1.0.0
     */
    private State $state = State::CREATED;

    /**
     * ### Registered kernel bootstrappers
     * @since 1.0.0
     *
     * @var list<\FireHub\Kernel\Bootstrapper|\FireHub\Kernel\Application\Bootstrapper>
     */
    private array $bootstrappers = [];

    /**
     * ### Registered kernel terminators
     * @since 1.0.0
     *
     * @var list<\FireHub\Kernel\Terminator|\FireHub\Kernel\Application\Terminator>
     */
    private array $terminators = [];

    /**
     * ### Registers a kernel bootstrapper
     *
     * Adds a global or application-specific bootstrapper to the initialization sequence.
     *
     * Bootstrappers are executed in their registration order.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Application::assertRegistrationAllowed() To validate lifecycle component registration.
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

        $this->assertRegistrationAllowed();

        $this->bootstrappers[] = $bootstrapper;

    }

    /**
     * ### Registers a kernel terminator
     *
     * Adds a global or application-specific terminator to the shutdown sequence.
     *
     * Terminators are executed in reverse registration order.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Application::assertRegistrationAllowed() To validate lifecycle component registration.
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

        $this->assertRegistrationAllowed();

        $this->terminators[] = $terminator;

    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If the kernel is in an invalid state.
     * @throws \FireHub\Kernel\Exception\KernelBootException If the kernel fails to boot.
     */
    public function boot ():void {

        if ($this->state === State::BOOTED) return;

        if ($this->state !== State::CREATED)
            throw new InvalidKernelStateException(
                'Cannot boot the application kernel because of invalid state.',
                [
                    'state' => $this->state
                ]
            );

        $this->state = State::BOOTING;

        try {

            $this->initialize();

            $this->state = State::BOOTED;

        } catch (Throwable $exception) {

            $this->state = State::FAILED;

            throw new KernelBootException(
                'Failed to boot the application kernel.',
                previous: $exception
            );

        }

    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If the kernel is in an invalid state.
     * @throws \FireHub\Kernel\Exception\KernelShutdownException If the kernel fails to shut down.
     */
    public function shutdown ():void {

        if ($this->state === State::TERMINATED) return;

        if ($this->state !== State::CREATED && $this->state !== State::BOOTED && $this->state !== State::FAILED)
            throw new InvalidKernelStateException(
                'Cannot shutdown the application kernel because of invalid state.',
                [
                    'state' => $this->state
                ]
            );

        $this->state = State::SHUTTING_DOWN;

        try {
            $this->terminate();

            $this->state = State::TERMINATED;

        } catch (Throwable $exception) {

            $this->state = State::FAILED;

            throw new KernelShutdownException(
                'Failed to shut down the application kernel.',
                previous: $exception
            );

        }

    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     */
    public function state ():State {

        return $this->state;

    }

    /**
     * ### Initializes application components
     *
     * Extension point for application-specific bootstrapping. The base implementation does nothing.
     * @since 1.0.0
     *
     * @uses \FireHub\Core\Boundary\Kernel\Bootstrapper::bootstrap() To bootstrap component.
     *
     * @return void
     */
    private function initialize ():void {

        foreach ($this->bootstrappers as $bootstrapper)
            $bootstrapper->bootstrap();

    }

    /**
     * ### Terminates application components
     *
     * Extension point for application-specific cleanup. The base implementation does nothing.
     * @since 1.0.0
     *
     * @uses \FireHub\Core\Boundary\Kernel\Terminator::terminate() To terminate component.
     * @uses \FireHub\Runtime\Arr\Transform::reverse() Reverses an array.
     *
     * @throws \FireHub\Core\Exception\Runtime\KernelException If any terminator fails to terminate.
     *
     * @return void
     */
    private function terminate ():void {

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

    /**
     * ### Validates lifecycle component registration
     *
     * Ensures that lifecycle components can only be registered before kernel initialization begins.
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If registration is not allowed.
     *
     * @return void
     */
    private function assertRegistrationAllowed ():void {

        if ($this->state !== State::CREATED)
            throw new InvalidKernelStateException(
                'Cannot register lifecycle components after kernel initialization has started.',
                [
                    'state' => $this->state
                ]
            );

    }

}