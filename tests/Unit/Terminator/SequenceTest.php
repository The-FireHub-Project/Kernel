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

namespace FireHub\Tests\Kernel\Unit\Terminator;

use FireHub\Testing\FireHubTestCase;
use FireHub\Kernel\Terminator;
use FireHub\Kernel\Terminator\Sequence;
use PHPUnit\Framework\Attributes\ {
    CoversClass, Group, Small
};
use Throwable;

/**
 * ### Tests kernel termination sequence
 * @since 1.0.0
 */
#[Small]
#[Group('terminator')]
#[CoversClass(Sequence::class)]
final class SequenceTest extends FireHubTestCase {

    /**
     * ### Tests execution of an empty sequence
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testExecuteEmpty ():void {

        $sequence = new Sequence;

        $sequence->execute();

        $this->expectNotToPerformAssertions();

    }

    /**
     * ### Tests execution of a single terminator
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testExecuteSingle ():void {

        $sequence = new Sequence;

        $terminator = $this->createMock(Terminator::class);

        $terminator->expects($this->once())
            ->method('terminate');

        $sequence->register($terminator, 0);

        $sequence->execute();

    }

    /**
     * ### Tests execution of multiple terminators
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testExecuteMultiple ():void {

        $sequence = new Sequence;

        $terminators = [];

        for ($i = 0; $i < 3; $i++) {

            $terminator = $this->createMock(Terminator::class);

            $terminator->expects($this->once())
                ->method('terminate');

            $terminators[] = $terminator;

        }

        foreach ($terminators as $terminator)
            $sequence->register($terminator, 0);

        $sequence->execute();

    }

    /**
     * ### Tests ascending execution order
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testExecuteOrder ():void {

        $sequence = new Sequence;

        $executed = [];

        foreach ([30, 10, 20] as $order) {

            $terminator = $this->createMock(Terminator::class);

            $terminator->method('terminate')
                ->willReturnCallback(
                    static function () use (&$executed, $order):void {
                        $executed[] = $order;
                    }
                );

            $sequence->register($terminator, $order);

        }

        $sequence->execute();

        self::assertSame([10, 20, 30], $executed);

    }

    /**
     * ### Tests stable execution order
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testExecuteEqualOrder ():void {

        $sequence = new Sequence;

        $executed = [];

        foreach (['first', 'second', 'third'] as $name) {

            $terminator = $this->createMock(Terminator::class);

            $terminator->method('terminate')
                ->willReturnCallback(
                    static function () use (&$executed, $name):void {
                        $executed[] = $name;
                    }
                );

            $sequence->register($terminator, 10);

        }

        $sequence->execute();

        self::assertSame(['first', 'second', 'third'], $executed);

    }

    /**
     * ### Tests negative execution priorities
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testExecuteNegativeOrder ():void {

        $sequence = new Sequence;

        $executed = [];

        foreach ([10, -100, 0, -10] as $order) {

            $terminator = $this->createMock(Terminator::class);

            $terminator->method('terminate')
                ->willReturnCallback(
                    static function () use (&$executed, $order):void {
                        $executed[] = $order;
                    }
                );

            $sequence->register($terminator, $order);

        }

        $sequence->execute();

        self::assertSame([-100, -10, 0, 10], $executed);

    }

    /**
     * ### Tests termination continues after a failure
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testExecuteContinuesOnFailure ():void {

        $sequence = new Sequence;

        $executed = [];

        foreach ([1, 2, 3] as $order) {

            $terminator = $this->createMock(Terminator::class);

            $terminator->method('terminate')
                ->willReturnCallback(
                    static function () use (&$executed, $order):void {

                        $executed[] = $order;

                        if ($order === 2)
                            throw new \RuntimeException('Termination failed.');

                    }
                );

            $sequence->register($terminator, $order);

        }

        try {

            $sequence->execute();

            self::fail('Expected termination exception was not thrown.');

        } catch (\RuntimeException $exception) {

            self::assertSame('Termination failed.', $exception->getMessage());

        }

        self::assertSame([1, 2, 3], $executed);

    }

    /**
     * ### Tests that the first exception is propagated
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testExecuteThrowsFirstFailure ():void {

        $sequence = new Sequence;

        $first_exception = new \RuntimeException('First failure.');
        $second_exception = new \RuntimeException('Second failure.');

        $first = $this->createMock(Terminator::class);
        $second = $this->createMock(Terminator::class);

        $first->expects($this->once())
            ->method('terminate')
            ->willThrowException($first_exception);

        $second->expects($this->once())
            ->method('terminate')
            ->willThrowException($second_exception);

        $sequence->register($first, 10);
        $sequence->register($second, 20);

        try {

            $sequence->execute();

            self::fail('Expected termination exception was not thrown.');

        } catch (\RuntimeException $exception) {

            self::assertSame($first_exception, $exception);

        }

    }

    /**
     * ### Tests repeated sequence execution
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testExecuteRepeated ():void {

        $sequence = new Sequence;

        $terminator = $this->createMock(Terminator::class);

        $terminator->expects($this->exactly(2))
            ->method('terminate');

        $sequence->register($terminator, 0);

        $sequence->execute();
        $sequence->execute();

    }

}