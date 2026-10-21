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
use FireHub\Runtime;
use Closure, Throwable, UnitEnum;

/**
 * ### Coordinates a configurable lifecycle
 *
 * Provides generic lifecycle execution and state management independently of the component owning the lifecycle.
 *
 * Lifecycle states are represented by an arbitrary enumeration.
 *
 * Each operation defines its permitted source states, successful state, failure state, and optional execution state.
 *
 * The engine also manages parent lifecycle dependencies and prevents termination while dependent lifecycles remain
 * active.
 * @since 1.0.0
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
     * ### Whether an operation is executing
     * @since 1.0.0
     */
    private bool $executing = false;

    /**
     * ### Whether component registration is closed
     * @since 1.0.0
     */
    private bool $registration_closed = false;

    /**
     * ### Constructor
     * @since 1.0.0
     *
     * @param TState $initial <p>
     * Initial lifecycle state.
     * </p>
     * @param null|\FireHub\Kernel\Lifecycle\Dependency\Boundary $parent [optional] <p>
     * Parent lifecycle dependency boundary.
     * </p>
     *
     * @return void
     */
    public function __construct (
        private readonly UnitEnum $initial,
        private readonly ?Boundary $parent = null
    ) {

        $this->state = $initial;

        $this->manager = new Manager;

        $this->dependencies = new Registry;

    }

    /**
     * ### Gets the lifecycle dependency boundary
     *
     * Exposes dependency acquisition while the owning lifecycle is available to dependent components.
     * @since 1.0.0
     *
     * @param Closure(TState):bool $is_available <p>
     * Determines whether the current state accepts new dependencies.
     * </p>
     *
     * @return \FireHub\Kernel\Lifecycle\Dependency\Boundary Lifecycle dependency boundary.
     */
    public function dependencyBoundary (Closure $is_available):Boundary {

        return new Boundary(
            fn ():bool => $is_available($this->state),
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
     * ### Executes registered startup callbacks
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Manager::start() To execute startup callbacks.
     *
     * @throws Throwable If a startup callback fails.
     *
     * @return void
     */
    public function start ():void {

        $this->manager->start();

    }

    /**
     * ### Executes registered shutdown callbacks
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Manager::stop() To execute shutdown callbacks.
     *
     * @throws Throwable If a shutdown callback fails.
     *
     * @return void
     */
    public function stop ():void {

        $this->manager->stop();

    }

    /**
     * ### Executes a lifecycle operation
     *
     * Validates the current state and executes the supplied callback.
     *
     * An optional entering state represents an operation in progress.
     *
     * On successful execution, the lifecycle transitions to the success state. On failure, it transitions to the
     * failure state and propagates the original exception through LifecycleExecutionException.
     *
     * Parent dependency acquisition and release are explicitly controlled by the operation rather than inferred from
     * enumeration case names.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Engine::assertStateType() To validate state types.
     * @uses \FireHub\Kernel\Lifecycle\Engine::releaseParent() To release the parent dependency.
     * @uses \FireHub\Kernel\Lifecycle\Dependency\Registry::assertEmpty() To validate no dependent lifecycles remain.
     * @uses \FireHub\Kernel\Lifecycle\Dependency\Boundary::acquire() To acquire a parent dependency lease.
     * @uses \FireHub\Runtime\Arr\Inspection::inArray() To check if the current state is in the allowed states.
     *
     * @param Closure():void $callback <p>
     * Operation callback.
     * </p>
     * @param non-empty-list<TState> $from <p>
     * States from which execution is permitted.
     * </p>
     * @param TState $success <p>
     * State after successful execution.
     * </p>
     * @param TState $failure <p>
     * State after failed execution.
     * </p>
     * @param null|TState $entering [optional] <p>
     * State while the operation is executing.
     * </p>
     * @param bool $acquire_parent [optional] <p>
     * Acquire a parent dependency before execution.
     * </p>
     * @param bool $release_parent [optional] <p>
     * Release the parent dependency after execution, including failure.
     * </p>
     * @param bool $require_no_dependents [optional] <p>
     * Reject execution while dependent lifecycles remain active.
     * </p>
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException If execution is not permitted.
     * @throws \FireHub\Kernel\Lifecycle\Exception\LifecycleExecutionException If the operation callback fails.
     *
     * @return void
     */
    public function execute (Closure $callback, array $from, UnitEnum $success, UnitEnum $failure, ?UnitEnum $entering = null, bool $acquire_parent = false, bool $release_parent = false, bool $require_no_dependents = false):void {

        if ($this->executing)
            throw new InvalidLifecycleStateException(
                'A lifecycle operation is already executing.'
            );

        $this->assertStateType($success);
        $this->assertStateType($failure);

        if ($entering !== null)
            $this->assertStateType($entering);

        foreach ($from as $allowed)
            $this->assertStateType($allowed);

        if ($from === [] || !Runtime\Arr\Inspection::inArray($from, $this->state))
            throw new InvalidLifecycleStateException(
                'The lifecycle operation is not allowed in the current state.'
            );

        if ($require_no_dependents)
            $this->dependencies->assertEmpty();

        if ($acquire_parent && $this->parent_lease === null)
            $this->parent_lease = $this->parent?->acquire();

        $this->registration_closed = true;
        $this->executing = true;

        if ($entering !== null)
            $this->state = $entering;

        try {

            $callback();

        } catch (Throwable $exception) {

            $this->state = $failure;

            if ($release_parent)
                $this->releaseParent();

            throw new LifecycleExecutionException(
                'Lifecycle operation failed.',
                previous: $exception
            );

        } finally {

            $this->executing = false;

        }

        $this->state = $success;

        if ($release_parent)
            $this->releaseParent();

    }

    /**
     * ### Gets the current lifecycle state
     * @since 1.0.0
     *
     * @return TState Current lifecycle state.
     */
    public function state ():UnitEnum {

        return $this->state;

    }

    /**
     * ### Validates lifecycle component registration
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException If registration is not allowed.
     *
     * @return void
     */
    private function assertRegistrationAllowed ():void {

        if ($this->registration_closed || $this->executing)
            throw new InvalidLifecycleStateException(
                'Lifecycle components cannot be registered after execution has begun.'
            );

    }

    /**
     * ### Validates the lifecycle state enumeration
     * @since 1.0.0
     *
     * @param UnitEnum $state <p>
     * State to validate.
     * </p>
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException If the state is not of the expected type.
     *
     * @return void
     */
    private function assertStateType (UnitEnum $state):void {

        if ($state::class !== $this->initial::class)
            throw new InvalidLifecycleStateException(
                'Lifecycle states must belong to the same enumeration.'
            );

    }

    /**
     * ### Releases the parent lifecycle dependency
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Dependency\Lease::release() To release the parent lease.
     *
     * @return void
     */
    private function releaseParent ():void {

        $this->parent_lease?->release();

        $this->parent_lease = null;

    }

}