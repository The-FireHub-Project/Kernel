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
    CoversClass, Group, Small
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
     * @since 1.0.0
     *
     * @return void
     */
    public function testInitialState ():void {

        self::assertSame(State::CREATED, new Lifecycle()->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testBootTransitions ():void {

        $lifecycle = new Lifecycle();

        $lifecycle->beginBoot();
        self::assertSame(State::BOOTING, $lifecycle->state());

        $lifecycle->completeBoot();
        self::assertSame(State::BOOTED, $lifecycle->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testShutdownTransitions ():void {

        $lifecycle = new Lifecycle();

        $lifecycle->beginShutdown();
        self::assertSame(State::SHUTTING_DOWN, $lifecycle->state());

        $lifecycle->completeShutdown();
        self::assertSame(State::TERMINATED, $lifecycle->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testFailedBootCanBeShutDown ():void {

        $lifecycle = new Lifecycle();
        $lifecycle->beginBoot();
        $lifecycle->fail();

        self::assertSame(State::FAILED, $lifecycle->state());

        $lifecycle->beginShutdown();
        $lifecycle->completeShutdown();

        self::assertSame(State::TERMINATED, $lifecycle->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testCannotBootTwice ():void {

        $lifecycle = new Lifecycle();
        $lifecycle->beginBoot();

        $this->expectException(InvalidKernelStateException::class);

        $lifecycle->beginBoot();

    }

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function testCannotCompleteBootBeforeBeginning ():void {

        $this->expectException(InvalidKernelStateException::class);

        new Lifecycle()->completeBoot();

    }

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function testCannotFailWhenIdle ():void {

        $this->expectException(InvalidKernelStateException::class);

        new Lifecycle()->fail();

    }

}