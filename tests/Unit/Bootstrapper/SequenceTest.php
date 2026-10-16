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

namespace FireHub\Tests\Kernel\Unit\Bootstrapper;

use FireHub\Testing\FireHubTestCase;
use FireHub\Kernel\Bootstrapper;
use FireHub\Kernel\Bootstrapper\Sequence;
use FireHub\Tests\Kernel\Stubs\BasicBootstrapper;
use PHPUnit\Framework\Attributes\ {
    CoversClass, Group, Small
};

/**
 * ### Tests kernel bootstrap sequence
 * @since 1.0.0
 */
#[Small]
#[Group('bootstrapper')]
#[CoversClass(Sequence::class)]
final class SequenceTest extends FireHubTestCase {

    /**
     * ### Tests execution of an empty sequence
     * @since 1.0.0
     *
     * @return void
     */
    public function testEmptySequence ():void {

        $sequence = new Sequence;

        $sequence->execute();

        self::assertTrue(true);

    }

    /**
     * ### Tests execution order
     * @since 1.0.0
     *
     * @return void
     */
    public function testExecutionOrder ():void {

        $sequence = new Sequence;

        $executed = [];

        foreach ([30, 10, 20] as $order) {

            $sequence->register(
                new BasicBootstrapper($order, $executed),
                $order
            );

        }

        $sequence->execute();

        self::assertSame([10, 20, 30], $executed);

    }

    /**
     * ### Tests stable ordering for equal priorities
     * @since 1.0.0
     *
     * @return void
     */
    public function testStableExecutionOrder ():void {

        $sequence = new Sequence;

        $executed = [];

        foreach (['first', 'second', 'third'] as $name) {

            $sequence->register(
                new  BasicBootstrapper($name, $executed),
                10
            );

        }

        $sequence->execute();

        self::assertSame(['first', 'second', 'third'], $executed);

    }

    /**
     * ### Tests that each bootstrapper executes once
     * @since 1.0.0
     *
     * @return void
     */
    public function testBootstrapperExecutesOnce ():void {

        $sequence = new Sequence;

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->expects($this->once())
            ->method('bootstrap');

        $sequence->register($bootstrapper, 0);

        $sequence->execute();

    }

    /**
     * ### Tests execution stops when a bootstrapper fails
     * @since 1.0.0
     *
     * @return void
     */
    public function testExecutionStopsOnFailure ():void {

        $sequence = new Sequence;

        $first = $this->createMock(Bootstrapper::class);
        $failing = $this->createMock(Bootstrapper::class);
        $last = $this->createMock(Bootstrapper::class);

        $first->expects($this->once())
            ->method('bootstrap');

        $failing->expects($this->once())
            ->method('bootstrap')
            ->willThrowException(new \RuntimeException('Bootstrap failed.'));

        $last->expects($this->never())
            ->method('bootstrap');

        $sequence->register($first, 10);
        $sequence->register($failing, 20);
        $sequence->register($last, 30);

        $this->expectException(\RuntimeException::class);

        $sequence->execute();

    }

}