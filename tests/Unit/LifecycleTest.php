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

namespace FireHub\Tests\Kernel\Unit;

use FireHub\Testing\FireHubTestCase;
use FireHub\Core\Meta\Enum\Kernel\State;
use FireHub\Kernel\Lifecycle;
use FireHub\Kernel\Exception\InvalidKernelStateException;
use PHPUnit\Framework\Attributes\ {
    CoversClass, DataProvider, Group, Small
};

/**
 * ### Test kernel lifecycle state transitions
 * @since 1.0.0
 */
#[Small]
#[Group('lifecycle')]
#[CoversClass(Lifecycle::class)]
final class LifecycleTest extends FireHubTestCase {

    /**
     * ### Tests the initial lifecycle state
     * @since 1.0.0
     */
    public function testInitialState ():void {

        $lifecycle = new Lifecycle;

        self::assertSame(State::CREATED, $lifecycle->state());

    }

    /**
     * ### Tests beginning kernel initialization
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testBeginBoot ():void {

        $lifecycle = new Lifecycle;

        $lifecycle->beginBoot();

        self::assertSame(State::BOOTING, $lifecycle->state());

    }

    /**
     * ### Tests completing kernel initialization
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testCompleteBoot ():void {

        $lifecycle = new Lifecycle;

        $lifecycle->beginBoot();
        $lifecycle->completeBoot();

        self::assertSame(State::BOOTED, $lifecycle->state());

    }

    /**
     * ### Tests beginning shutdown before initialization
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testBeginShutdownBeforeBoot ():void {

        $lifecycle = new Lifecycle;

        $lifecycle->beginShutdown();

        self::assertSame(State::SHUTTING_DOWN, $lifecycle->state());

    }

    /**
     * ### Tests beginning shutdown after initialization
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testBeginShutdownAfterBoot ():void {

        $lifecycle = new Lifecycle;

        $lifecycle->beginBoot();
        $lifecycle->completeBoot();
        $lifecycle->beginShutdown();

        self::assertSame(State::SHUTTING_DOWN, $lifecycle->state());

    }

    /**
     * ### Tests beginning shutdown after initialization failure
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testBeginShutdownAfterBootFailure ():void {

        $lifecycle = new Lifecycle;

        $lifecycle->beginBoot();
        $lifecycle->fail();
        $lifecycle->beginShutdown();

        self::assertSame(State::SHUTTING_DOWN, $lifecycle->state());

    }

    /**
     * ### Tests completing kernel shutdown
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testCompleteShutdown ():void {

        $lifecycle = new Lifecycle;

        $lifecycle->beginShutdown();
        $lifecycle->completeShutdown();

        self::assertSame(State::TERMINATED, $lifecycle->state());

    }

    /**
     * ### Tests marking initialization as failed
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testFailDuringBoot ():void {

        $lifecycle = new Lifecycle;

        $lifecycle->beginBoot();
        $lifecycle->fail();

        self::assertSame(State::BOOT_FAILED, $lifecycle->state());

    }

    /**
     * ### Tests marking shutdown as failed
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testFailDuringShutdown ():void {

        $lifecycle = new Lifecycle;

        $lifecycle->beginShutdown();
        $lifecycle->fail();

        self::assertSame(State::SHUTDOWN_FAILED, $lifecycle->state());

    }

    /**
     * ### Tests the complete successful lifecycle
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testCompleteLifecycle ():void {

        $lifecycle = new Lifecycle;

        self::assertSame(State::CREATED, $lifecycle->state());

        $lifecycle->beginBoot();

        self::assertSame(State::BOOTING, $lifecycle->state());

        $lifecycle->completeBoot();

        self::assertSame(State::BOOTED, $lifecycle->state());

        $lifecycle->beginShutdown();

        self::assertSame(State::SHUTTING_DOWN, $lifecycle->state());

        $lifecycle->completeShutdown();

        self::assertSame(State::TERMINATED, $lifecycle->state());

    }

    /**
     * ### Tests invalid lifecycle transitions
     * @since 1.0.0
     *
     * @param list<non-empty-string> $operations
     * @param non-empty-string $operation
     * @param State $expected_state
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    #[DataProvider('invalidTransitionProvider')]
    public function testInvalidTransition (array $operations, string $operation, State $expected_state):void {

        $lifecycle = new Lifecycle;

        foreach ($operations as $method)
            $lifecycle->{$method}();

        self::assertSame($expected_state, $lifecycle->state());

        try {

            $lifecycle->{$operation}();

            self::fail('Expected InvalidKernelStateException was not thrown.');

        } catch (InvalidKernelStateException) {

            self::assertSame($expected_state, $lifecycle->state());

        }

    }

    /**
     * ### Provides invalid lifecycle transitions
     * @since 1.0.0
     *
     * @return iterable<string, array{
     *     operations: list<non-empty-string>,
     *     operation: non-empty-string,
     *     expected_state: State
     * }>
     */
    public static function invalidTransitionProvider ():iterable {

        yield 'complete boot before beginning boot' => [
            'operations' => [],
            'operation' => 'completeBoot',
            'expected_state' => State::CREATED
        ];

        yield 'complete shutdown before beginning shutdown' => [
            'operations' => [],
            'operation' => 'completeShutdown',
            'expected_state' => State::CREATED
        ];

        yield 'fail before beginning lifecycle operation' => [
            'operations' => [],
            'operation' => 'fail',
            'expected_state' => State::CREATED
        ];

        yield 'begin boot while booting' => [
            'operations' => ['beginBoot'],
            'operation' => 'beginBoot',
            'expected_state' => State::BOOTING
        ];

        yield 'complete boot twice' => [
            'operations' => ['beginBoot', 'completeBoot'],
            'operation' => 'completeBoot',
            'expected_state' => State::BOOTED
        ];

        yield 'begin boot after successful boot' => [
            'operations' => ['beginBoot', 'completeBoot'],
            'operation' => 'beginBoot',
            'expected_state' => State::BOOTED
        ];

        yield 'begin shutdown while booting' => [
            'operations' => ['beginBoot'],
            'operation' => 'beginShutdown',
            'expected_state' => State::BOOTING
        ];

        yield 'fail after successful boot' => [
            'operations' => ['beginBoot', 'completeBoot'],
            'operation' => 'fail',
            'expected_state' => State::BOOTED
        ];

        yield 'begin boot after boot failure' => [
            'operations' => ['beginBoot', 'fail'],
            'operation' => 'beginBoot',
            'expected_state' => State::BOOT_FAILED
        ];

        yield 'complete boot after boot failure' => [
            'operations' => ['beginBoot', 'fail'],
            'operation' => 'completeBoot',
            'expected_state' => State::BOOT_FAILED
        ];

        yield 'fail twice after boot failure' => [
            'operations' => ['beginBoot', 'fail'],
            'operation' => 'fail',
            'expected_state' => State::BOOT_FAILED
        ];

        yield 'begin shutdown while shutting down' => [
            'operations' => ['beginShutdown'],
            'operation' => 'beginShutdown',
            'expected_state' => State::SHUTTING_DOWN
        ];

        yield 'complete shutdown twice' => [
            'operations' => ['beginShutdown', 'completeShutdown'],
            'operation' => 'completeShutdown',
            'expected_state' => State::TERMINATED
        ];

        yield 'begin shutdown after termination' => [
            'operations' => ['beginShutdown', 'completeShutdown'],
            'operation' => 'beginShutdown',
            'expected_state' => State::TERMINATED
        ];

        yield 'begin boot after termination' => [
            'operations' => ['beginShutdown', 'completeShutdown'],
            'operation' => 'beginBoot',
            'expected_state' => State::TERMINATED
        ];

        yield 'fail after termination' => [
            'operations' => ['beginShutdown', 'completeShutdown'],
            'operation' => 'fail',
            'expected_state' => State::TERMINATED
        ];

        yield 'begin shutdown after shutdown failure' => [
            'operations' => ['beginShutdown', 'fail'],
            'operation' => 'beginShutdown',
            'expected_state' => State::SHUTDOWN_FAILED
        ];

        yield 'complete shutdown after shutdown failure' => [
            'operations' => ['beginShutdown', 'fail'],
            'operation' => 'completeShutdown',
            'expected_state' => State::SHUTDOWN_FAILED
        ];

        yield 'fail twice after shutdown failure' => [
            'operations' => ['beginShutdown', 'fail'],
            'operation' => 'fail',
            'expected_state' => State::SHUTDOWN_FAILED
        ];

    }

}