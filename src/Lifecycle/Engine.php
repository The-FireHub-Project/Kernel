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

use FireHub\Kernel\Lifecycle\Dependency\ {
    Boundary, Lease, Registry
};
use FireHub\Kernel\Lifecycle\Exception\ {
    InvalidLifecycleStateException, LifecycleExecutionException
};
use Closure, Throwable, UnitEnum;

/**
 * ### Coordinates a configurable lifecycle
 *
 * Provides a reusable lifecycle state machine and execution controller independent of the component owning the
 * lifecycle.
 *
 * Each engine instance maintains its own state, startup callbacks, and shutdown callbacks.
 *
 * Lifecycle states are supplied during construction and remain associated with the engine throughout its lifetime.
 *
 * Startup and shutdown operations are guarded by explicit state transitions. Startup failures prevent further
 * initialization, while shutdown remains available after a startup failure to allow resource cleanup.
 *
 * Repeated startup calls after a successful startup and repeated shutdown calls after a successful shutdown are
 * treated as no-ops.
 *
 * The engine does not expose domain-specific exceptions. Lifecycle owners are responsible for translating generic
 * failures at their public API boundaries.
 *
 * @template TState of UnitEnum
 */
final class Engine {

    /**
     * ### Current lifecycle state
     * @since 1.0.0
     *
     * @var TState
     */
    private UnitEnum $state;

    /**
     * ### Lifecycle component manager
     * @since 1.0.0
     */
    private Manager $manager;

    /**
     * ### Dependent lifecycle registry
     * @since 1.0.0
     */
    private readonly Registry $dependencies;

    /**
     * ### Active parent lifecycle dependency
     * @since 1.0.0
     */
    private ?Lease $parent_lease = null;

    /**
     * ### Constructor
     * @since 1.0.0
     *
     * @param \FireHub\Kernel\Lifecycle\States<TState> $states <p>
     * Lifecycle state configuration.
     * </p>
     * @param null|\FireHub\Kernel\Lifecycle\Dependency\Boundary $parent [optional] <p>
     * Parent lifecycle dependency boundary.
     * </p>
     *
     * @return void
     */
    public function __construct (
        private readonly States $states,
        private readonly ?Boundary $parent = null
    ) {

        $this->manager = new Manager;

        $this->dependencies = new Registry;

        $this->state = $states->created;

    }

    /**
     * ### Gets the lifecycle dependency boundary
     *
     * Returns a restricted boundary that allows dependent lifecycle engines to acquire ownership while this
     * lifecycle is active.
     * @since 1.0.0
     *
     * @return \FireHub\Kernel\Lifecycle\Dependency\Boundary Lifecycle dependency boundary.
     */
    public function dependencyBoundary ():Boundary {

        return new Boundary(
            fn ():bool => $this->state === $this->states->started,
            $this->dependencies
        );

    }

    /**
     * ### Registers a startup callback
     *
     * Registers a callback that executes during lifecycle startup.
     *
     * Registration is permitted only before the lifecycle has started.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Engine::assertRegistrationAllowed() To validate registration.
     * @uses \FireHub\Kernel\Lifecycle\Manager::registerStartup() To register a startup callback.
     *
     * @param Closure():void $callback <p>
     * Startup callback.
     * </p>
     * @param int $order [optional] <p>
     * Execution order.
     * </p>
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException If registration is not allowed.
     *
     * @return void
     */
    public function registerStartup (Closure $callback, int $order = 0):void {

        $this->assertRegistrationAllowed();

        $this->manager->registerStartup($callback, $order);

    }

    /**
     * ### Registers a shutdown callback
     *
     * Registers a callback that executes during lifecycle shutdown.
     *
     * Registration is permitted only before the lifecycle has started.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Engine::assertRegistrationAllowed() To validate registration.
     * @uses \FireHub\Kernel\Lifecycle\Manager::registerShutdown() To register a shutdown callback.
     *
     * @param Closure():void $callback <p>
     * Shutdown callback.
     * </p>
     * @param int $order [optional] <p>
     * Execution order.
     * </p>
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException If registration is not allowed.
     *
     * @return void
     */
    public function registerShutdown (Closure $callback, int $order = 0):void {

        $this->assertRegistrationAllowed();

        $this->manager->registerShutdown($callback, $order);

    }

    /**
     * ### Starts the lifecycle
     *
     * Transitions the lifecycle from its created state into the starting state and executes registered startup
     * callbacks.
     *
     * Successful execution transitions the lifecycle into the started state.
     *
     * If execution fails, the lifecycle enters the start-failed state and the original failure is preserved as the
     * previous exception.
     *
     * Calling this method when the lifecycle is already started has no effect.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Dependency\Boundary::acquire() To acquire a dependency lease.
     * @uses \FireHub\Kernel\Lifecycle\Manager::start() To execute startup callbacks.
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException If startup is not allowed.
     * @throws \FireHub\Kernel\Lifecycle\Exception\LifecycleExecutionException If a startup callback fails.
     *
     * @return void
     */
    public function start ():void {

        if ($this->state === $this->states->started) return;

        if ($this->state !== $this->states->created)
            throw new InvalidLifecycleStateException(
                'Cannot start the lifecycle in the current state.'
            );

        $this->parent_lease = $this->parent?->acquire();

        $this->state = $this->states->starting;

        try {

            $this->manager->start();

        } catch (Throwable $exception) {

            $this->state = $this->states->start_failed;

            throw new LifecycleExecutionException(
                'Lifecycle startup failed.',
                previous: $exception
            );

        }

        $this->state = $this->states->started;

    }

    /**
     * ### Stops the lifecycle
     *
     * Begins lifecycle shutdown and executes registered shutdown callbacks.
     *
     * Shutdown is permitted from the created, started, and start-failed states. This allows cleanup even when the
     * startup did not complete successfully.
     *
     * Successful execution transitions the lifecycle into the stopped state.
     *
     * If shutdown execution fails, the lifecycle enters the stop-failed state. All shutdown callbacks are
     * attempted, and the parent dependency is released even if one fails.
     *
     * Calling this method when the lifecycle is already stopped has no effect.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Dependency\Registry::hasActive() To check for active dependencies.
     * @uses \FireHub\Kernel\Lifecycle\Manager::stop() To execute shutdown callbacks.
     * @uses \FireHub\Kernel\Lifecycle\Dependency\Lease::release() To release the dependency lease.
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException If shutdown is not allowed.
     * @throws \FireHub\Kernel\Lifecycle\Exception\LifecycleExecutionException If a shutdown callback fails.
     *
     * @return void
     */
    public function stop ():void {

        if ($this->state === $this->states->stopped) return;

        if (
            $this->state !== $this->states->created
            && $this->state !== $this->states->started
            && $this->state !== $this->states->start_failed
        )
            throw new InvalidLifecycleStateException(
                'Cannot stop the lifecycle in the current state.'
            );

        $this->dependencies->assertEmpty();

        if ($this->parent !== null && $this->parent_lease === null)
            $this->parent_lease = $this->parent->acquire();

        $this->state = $this->states->stopping;

        try {

            $this->manager->stop();

        } catch (Throwable $exception) {

            $this->state = $this->states->stop_failed;
            $this->parent_lease?->release();
            $this->parent_lease = null;

            throw new LifecycleExecutionException(
                'Lifecycle shutdown failed.',
                previous: $exception
            );

        }

        $this->state = $this->states->stopped;

        $this->parent_lease?->release();

        $this->parent_lease = null;

    }

    /**
     * ### Gets the current lifecycle state
     *
     * Returns the concrete enumeration case supplied by the lifecycle owner.
     *
     * No state conversion or domain-specific mapping is performed.
     * @since 1.0.0
     *
     * @return TState Current lifecycle state.
     */
    public function state ():UnitEnum {

        return $this->state;

    }

    /**
     * ### Validates lifecycle component registration
     *
     * Ensures that lifecycle components can only be registered while the lifecycle remains in its initially created
     * state.
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException If registration is not allowed.
     *
     * @return void
     */
    private function assertRegistrationAllowed ():void {

        if ($this->state !== $this->states->created)
            throw new InvalidLifecycleStateException(
                'Lifecycle components cannot be registered after startup has begun.'
            );

    }

}