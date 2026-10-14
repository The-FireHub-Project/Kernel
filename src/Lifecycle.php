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

use FireHub\Core\Meta\Enum\Kernel\State;
use FireHub\Kernel\Exception\InvalidKernelStateException;

/**
 * ### Manages kernel lifecycle state transitions
 *
 * Encapsulates the kernel state machine and ensures that lifecycle transitions happen only from valid states. This
 * class does not execute lifecycle components.
 * @since 1.0.0
 */
final class Lifecycle {

    /**
     * ### Current lifecycle state
     * @since 1.0.0
     */
    private State $state = State::CREATED;

    /**
     * ### Gets the current lifecycle state
     * @since 1.0.0
     *
     * @return State Current lifecycle state.
     */
    public function state ():State {

        return $this->state;

    }

    /**
     * ### Begins kernel initialization
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle::transition() Performs a guarded lifecycle transition.
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If the kernel cannot begin booting.
     *
     * @return void
     */
    public function beginBoot ():void {

        $this->transition(State::CREATED, State::BOOTING);

    }

    /**
     * ### Completes kernel initialization
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle::transition() Performs a guarded lifecycle transition.
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If the kernel is not booting.
     *
     * @return void
     */
    public function completeBoot ():void {

        $this->transition(State::BOOTING, State::BOOTED);

    }

    /**
     * ### Begins kernel termination
     *
     * Shutdown may begin before initialization, after a successful initialization,
     * or after an initialization failure.
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If shutdown is not allowed.
     *
     * @return void
     */
    public function beginShutdown ():void {

        if ($this->state !== State::CREATED && $this->state !== State::BOOTED && $this->state !== State::BOOT_FAILED)
            throw new InvalidKernelStateException(
                'Cannot begin kernel shutdown in the current state.',
                [
                    'state' => $this->state
                ]
            );

        $this->state = State::SHUTTING_DOWN;

    }

    /**
     * ### Completes kernel termination
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle::transition() Performs a guarded lifecycle transition.
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If the kernel is not shutting down.
     *
     * @return void
     */
    public function completeShutdown ():void {

        $this->transition(State::SHUTTING_DOWN, State::TERMINATED);

    }

    /**
     * ### Marks an active lifecycle operation as failed
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If no lifecycle operation is active.
     *
     * @return void
     */
    public function fail ():void {

        $this->state = match ($this->state) {
            State::BOOTING => State::BOOT_FAILED,
            State::SHUTTING_DOWN => State::SHUTDOWN_FAILED,
            default => throw new InvalidKernelStateException(
                'Cannot mark the kernel as failed outside an active lifecycle operation.',
                [
                    'state' => $this->state
                ]
            )

        };

    }

    /**
     * ### Performs a guarded lifecycle transition
     * @since 1.0.0
     *
     * @param State $expected <p>
     * Expected current state.
     * </p>
     * @param State $next <p>
     * Next state.
     * </p>
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException If the current state does not match.
     *
     * @return void
     */
    private function transition (State $expected, State $next):void {

        if ($this->state !== $expected)
            throw new InvalidKernelStateException(
                'Cannot perform the requested kernel lifecycle transition.',
                [
                    'state' => $this->state,
                    'expected' => $expected,
                    'next' => $next
                ]
            );

        $this->state = $next;

    }

}