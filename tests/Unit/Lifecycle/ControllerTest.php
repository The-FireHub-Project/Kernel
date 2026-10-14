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
use FireHub\Core\Meta\Enum\Kernel\State;
use FireHub\Kernel\Lifecycle\Controller;
use FireHub\Kernel\Exception\InvalidKernelStateException;
use FireHub\Tests\Kernel\Stubs\ {
    AppEventBootTerm, BasicBootstrapper, BasicTerminator, EventBootTerm
};
use PHPUnit\Framework\Attributes\ {
    CoversClass, Group, Small
};

/**
 * ### Test kernel lifecycle coordination
 * @since 1.0.0
 */
#[Small]
#[Group('lifecycle')]
#[CoversClass(Controller::class)]
final class ControllerTest extends FireHubTestCase {

    /**
     * ### Tests the initial lifecycle state
     * @since 1.0.0
     *
     * @return void
     */
    public function testInitialState ():void {

        $controller = new Controller();

        self::assertSame(State::CREATED, $controller->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testBoot ():void {

        $controller = new Controller();

        $controller->boot();

        self::assertSame(State::BOOTED, $controller->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testShutdown ():void {

        $controller = new Controller();

        $controller->boot();
        $controller->shutdown();

        self::assertSame(State::TERMINATED, $controller->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testShutdownFromCreatedState ():void {

        $controller = new Controller();

        $controller->shutdown();

        self::assertSame(State::TERMINATED, $controller->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testBootExecutesBootstrapper ():void {

        $controller = new Controller();
        $calls = 0;

        $controller->registerBootstrapper(new BasicBootstrapper($calls), 0);

        $controller->boot();

        self::assertSame(1, $calls);
        self::assertSame(State::BOOTED, $controller->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testShutdownExecutesTerminator ():void {

        $controller = new Controller();
        $calls = 0;

        $controller->registerTerminator(new BasicTerminator($calls), 0);

        $controller->boot();
        $controller->shutdown();

        self::assertSame(1, $calls);
        self::assertSame(State::TERMINATED, $controller->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testBootIsIdempotent ():void {

        $controller = new Controller();
        $calls = 0;

        $controller->registerBootstrapper(new BasicBootstrapper($calls), 0);

        $controller->boot();
        $controller->boot();

        self::assertSame(1, $calls);
        self::assertSame(State::BOOTED, $controller->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testShutdownIsIdempotent ():void {

        $controller = new Controller();
        $calls = 0;

        $controller->registerTerminator(new BasicTerminator($calls), 0);

        $controller->boot();
        $controller->shutdown();
        $controller->shutdown();

        self::assertSame(1, $calls);
        self::assertSame(State::TERMINATED, $controller->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testLifecycleExecutionOrder ():void {

        $controller = new Controller();
        $events = [];

        $global = new EventBootTerm($events);
        $application = new AppEventBootTerm($events);

        $controller->registerBootstrapper($global, -100);
        $controller->registerBootstrapper($application, 100);

        $controller->registerTerminator($global, 100);
        $controller->registerTerminator($application, -100);

        $controller->boot();
        $controller->shutdown();

        self::assertSame([
            'global',
            'application',
            'application',
            'global'
        ], $events);

        self::assertSame(State::TERMINATED, $controller->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testCannotRegisterBootstrapperAfterBoot ():void {

        $controller = new Controller();
        $calls = 0;

        $controller->boot();

        $this->expectException(InvalidKernelStateException::class);

        $controller->registerBootstrapper(new BasicBootstrapper($calls), 0);

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testCannotRegisterTerminatorAfterBoot ():void {

        $controller = new Controller();
        $calls = 0;

        $controller->boot();

        $this->expectException(InvalidKernelStateException::class);

        $controller->registerTerminator(new BasicTerminator($calls), 0);

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testCannotRegisterAfterShutdown ():void {

        $controller = new Controller();
        $calls = 0;

        $controller->shutdown();

        $this->expectException(InvalidKernelStateException::class);

        $controller->registerBootstrapper(new BasicBootstrapper($calls), 0);

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testCannotBootAfterShutdown ():void {

        $controller = new Controller();

        $controller->shutdown();

        $this->expectException(InvalidKernelStateException::class);

        $controller->boot();

    }

}