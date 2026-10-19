<?php declare(strict_types = 1);

/**
 * This file is part of the FireHub Project ecosystem
 *
 * @author Danijel Galić <danijel.galic@outlook.com>
 * @copyright 2026-present The FireHub Project - All rights reserved
 * @license https://opensource.org/license/Apache-2-0 Apache License, Version 2.0
 *
 * @php-version >=8.1
 * @package Kernel\Tests
 */

namespace FireHub\Tests\Kernel\Unit\Lifecycle;

use FireHub\Testing\FireHubTestCase;
use FireHub\Kernel\Lifecycle\Engine;
use FireHub\Kernel\Lifecycle\Exception\ {
    InvalidLifecycleStateException, LifecycleExecutionException
};
use FireHub\Tests\Kernel\Stubs\ {
    AnotherState, State
};
use PHPUnit\Framework\Attributes\ {
    CoversClass, Group, Small
};
use RuntimeException;

/**
 * ### Test configurable lifecycle
 * @since 1.0.0
 */
#[Small]
#[Group('lifecycle')]
#[CoversClass(Engine::class)]
final class EngineTest extends FireHubTestCase {

    /**
     * ### Tests the initial lifecycle state
     * @since 1.0.0
     *
     * @return void
     */
    public function testInitialState ():void {

        $engine = new Engine(State::CREATED);

        self::assertSame(
            State::CREATED,
            $engine->state()
        );

    }

    /**
     * ### Tests successful execution and intermediate state
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException
     * @throws \FireHub\Kernel\Lifecycle\Exception\LifecycleExecutionException
     *
     * @return void
     */
    public function testSuccessfulTransitionAndEnteringState ():void {

        $engine = new Engine(State::CREATED);

        $seen = null;

        $engine->execute(
            callback: static function () use ($engine, &$seen):void {

                $seen = $engine->state();

            },
            from: [State::CREATED],
            success: State::RUNNING,
            failure: State::FAILED,
            entering: State::STARTING
        );

        self::assertSame(State::STARTING, $seen);

        self::assertSame(
            State::RUNNING,
            $engine->state()
        );

    }

    /**
     * ### Tests failure propagation and state transition
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException
     * @throws \FireHub\Kernel\Lifecycle\Exception\LifecycleExecutionException
     *
     * @return void
     */
    public function testFailurePreservesPreviousException ():void {

        $engine = new Engine(State::CREATED);

        $failure = new RuntimeException('Failed.');

        try {

            $engine->execute(
                callback: static function () use ($failure):void {

                    throw $failure;

                },
                from: [State::CREATED],
                success: State::RUNNING,
                failure: State::FAILED
            );

            self::fail('Expected lifecycle execution failure.');

        } catch (LifecycleExecutionException $exception) {

            self::assertSame(
                $failure,
                $exception->getPrevious()
            );

            self::assertSame(
                State::FAILED,
                $engine->state()
            );

        }

    }

    /**
     * ### Tests invalid source state rejection
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException
     * @throws \FireHub\Kernel\Lifecycle\Exception\LifecycleExecutionException
     *
     * @return void
     */
    public function testInvalidSourceStateDoesNotExecuteCallback ():void {

        $engine = new Engine(State::CREATED);

        $called = false;

        try {

            $engine->execute(
                callback: static function () use (&$called):void {

                    $called = true;

                },
                from: [State::RUNNING],
                success: State::STOPPED,
                failure: State::FAILED
            );

            self::fail('Expected invalid lifecycle state.');

        } catch (InvalidLifecycleStateException) {

            self::assertFalse($called);

            self::assertSame(
                State::CREATED,
                $engine->state()
            );

        }

    }

    /**
     * ### Tests rejection of states from another enumeration
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException
     * @throws \FireHub\Kernel\Lifecycle\Exception\LifecycleExecutionException
     *
     * @return void
     */
    public function testRejectsStatesFromAnotherEnum ():void {

        $engine = new Engine(State::CREATED);

        $this->expectException(
            InvalidLifecycleStateException::class
        );

        $engine->execute(
            callback: static function ():void {},
            from: [State::CREATED],
            success: AnotherState::DONE,
            failure: State::FAILED
        );

    }

    /**
     * ### Tests registration is prohibited after execution
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException
     * @throws \FireHub\Kernel\Lifecycle\Exception\LifecycleExecutionException
     *
     * @return void
     */
    public function testRegistrationClosesAfterExecution ():void {

        $engine = new Engine(State::CREATED);

        $engine->execute(
            callback: static function ():void {},
            from: [State::CREATED],
            success: State::RUNNING,
            failure: State::FAILED
        );

        $this->expectException(
            InvalidLifecycleStateException::class
        );

        $engine->registerStartup(
            static function ():void {}
        );

    }

    /**
     * ### Tests nested lifecycle operations are prohibited
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException
     * @throws \FireHub\Kernel\Lifecycle\Exception\LifecycleExecutionException
     *
     * @return void
     */
    public function testRejectsNestedExecution ():void {

        $engine = new Engine(State::CREATED);

        $nested_rejected = false;

        $engine->execute(
            callback: static function () use (
                $engine,
                &$nested_rejected
            ):void {

                try {

                    $engine->execute(
                        callback: static function ():void {},
                        from: [State::STARTING],
                        success: State::RUNNING,
                        failure: State::FAILED
                    );

                } catch (InvalidLifecycleStateException) {

                    $nested_rejected = true;

                }

            },
            from: [State::CREATED],
            success: State::RUNNING,
            failure: State::FAILED,
            entering: State::STARTING
        );

        self::assertTrue($nested_rejected);

        self::assertSame(
            State::RUNNING,
            $engine->state()
        );

    }

    /**
     * ### Tests parent shutdown is blocked by active dependencies
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException
     * @throws \FireHub\Kernel\Lifecycle\Exception\LifecycleExecutionException
     *
     * @return void
     */
    public function testParentDependencyBlocksShutdownUntilReleased ():void {

        $parent = new Engine(State::CREATED);

        $parent->execute(
            callback: static function ():void {},
            from: [State::CREATED],
            success: State::RUNNING,
            failure: State::FAILED
        );

        $child = new Engine(
            State::CREATED,
            $parent->dependencyBoundary(
                static fn (State $state):bool =>
                    $state === State::RUNNING
            )
        );

        $child->execute(
            callback: static function ():void {},
            from: [State::CREATED],
            success: State::RUNNING,
            failure: State::FAILED,
            acquire_parent: true
        );

        try {

            $parent->execute(
                callback: static function ():void {},
                from: [State::RUNNING],
                success: State::STOPPED,
                failure: State::FAILED,
                require_no_dependents: true
            );

            self::fail('Expected active dependency rejection.');

        } catch (InvalidLifecycleStateException) {

            self::assertSame(
                State::RUNNING,
                $parent->state()
            );

        }

        $child->execute(
            callback: static function ():void {},
            from: [State::RUNNING],
            success: State::STOPPED,
            failure: State::FAILED,
            release_parent: true
        );

        $parent->execute(
            callback: static function ():void {},
            from: [State::RUNNING],
            success: State::STOPPED,
            failure: State::FAILED,
            require_no_dependents: true
        );

        self::assertSame(
            State::STOPPED,
            $parent->state()
        );

    }

}