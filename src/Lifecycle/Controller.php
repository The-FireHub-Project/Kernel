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
use FireHub\Kernel\Lifecycle;
use FireHub\Kernel\Exception\ {
    InvalidKernelStateException, KernelBootException, KernelShutdownException
};
use Throwable;

/**
 * ### Coordinates the kernel lifecycle
 *
 * Owns the lifecycle state machine and component execution manager. Validates
 * registration and orchestrates boot and shutdown operations.
 *
 * The controller is kernel-type agnostic. Application-specific registration
 * restrictions are enforced by the public Application facade.
 * @since 1.0.0
 */
final readonly class Controller implements Kernel {

    /**
     * ### Lifecycle state machine
     * @since 1.0.0
     */
    private Lifecycle $lifecycle;

    /**
     * ### Lifecycle component manager
     * @since 1.0.0
     */
    private Manager $manager;

    /**
     * ### Constructor
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct () {

        $this->lifecycle = new Lifecycle;

        $this->manager = new Manager;

    }

    /**
     * ### Registers a bootstrapper before initialization
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::assertRegistrationAllowed() To validate registration.
     * @uses \FireHub\Kernel\Lifecycle\Manager::registerBootstrapper() To register bootstrapper.
     *
     * @param \FireHub\Core\Boundary\Kernel\Bootstrapper $bootstrapper <p>
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

        $this->assertRegistrationAllowed();

        $this->manager->registerBootstrapper($bootstrapper, $order);

    }

    /**
     * ### Registers a terminator before initialization
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::assertRegistrationAllowed() To validate registration.
     * @uses \FireHub\Kernel\Lifecycle\Manager::registerTerminator() To register terminator.
     *
     * @param \FireHub\Core\Boundary\Kernel\Terminator $terminator <p>
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

        $this->assertRegistrationAllowed();

        $this->manager->registerTerminator($terminator, $order);

    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::state() To get the current state.
     * @uses \FireHub\Kernel\Lifecycle\Manager::initialize() To initialize.
     * @uses \FireHub\Kernel\Lifecycle::beginBoot() To begin booting.
     * @uses \FireHub\Kernel\Lifecycle::completeBoot() To complete booting.
     * @uses \FireHub\Kernel\Lifecycle::fail() To fail booting.
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If booting is not allowed.
     * @throws \FireHub\Kernel\Exception\KernelBootException If a bootstrapper fails.
     */
    public function boot ():void {

        if ($this->state() === State::BOOTED) return;

        $this->lifecycle->beginBoot();

        try {

            $this->manager->initialize();

            $this->lifecycle->completeBoot();

        } catch (Throwable $exception) {

            $this->lifecycle->fail();

            throw new KernelBootException(
                'Failed to boot the kernel.',
                previous: $exception
            );

        }

    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::state() To get the current state.
     * @uses \FireHub\Kernel\Lifecycle\Manager::terminate() To terminate.
     * @uses \FireHub\Kernel\Lifecycle::beginShutdown() To begin booting.
     * @uses \FireHub\Kernel\Lifecycle::completeShutdown() To complete shutdown.
     * @uses \FireHub\Kernel\Lifecycle::fail() To fail booting.
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If shutdown is not allowed.
     * @throws \FireHub\Kernel\Exception\KernelShutdownException If a terminator fails.
     */
    public function shutdown ():void {

        if ($this->state() === State::TERMINATED) return;

        $this->lifecycle->beginShutdown();

        try {

            $this->manager->terminate();

            $this->lifecycle->completeShutdown();

        } catch (Throwable $exception) {

            $this->lifecycle->fail();

            throw new KernelShutdownException(
                'Failed to shut down the kernel.',
                previous: $exception
            );

        }

    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle::state() To get the current state.
     */
    public function state ():State {

        return $this->lifecycle->state();

    }

    /**
     * ### Validates lifecycle component registration
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Controller::state() To get the current state.
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If registration is not allowed.
     *
     * @return void
     */
    private function assertRegistrationAllowed ():void {

        if ($this->state() !== State::CREATED)
            throw new InvalidKernelStateException(
                'Cannot register lifecycle components after kernel initialization has started.',
                [
                    'state' => $this->state()
                ]
            );

    }

}