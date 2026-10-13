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
use FireHub\Kernel\Application;
use FireHub\Kernel\ {
    Bootstrapper, Terminator,
    Application\Bootstrapper as ApplicationBootstrapper, Application\Terminator as ApplicationTerminator,
};
use FireHub\Tests\Kernel\Stubs\ {
    AppEventBootTerm, BasicBootstrapper, BasicTerminator, EventBootTerm
};
use PHPUnit\Framework\Attributes\ {
    CoversClass, Group, Small
};

/**
 * ### Test application kernel lifecycle management
 * @since 1.0.0
 */
#[Small]
#[Group('application')]
#[CoversClass(Application::class)]
final class ApplicationTest extends FireHubTestCase {

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function testInitialState ():void {

        self::assertSame(State::CREATED, new Application()->state());

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

        $kernel = new Application();

        $kernel->boot();

        self::assertSame(State::BOOTED, $kernel->state());

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

        $calls = 0;
        $kernel = new Application();
        $kernel->registerBootstrapper(new BasicBootstrapper($calls));

        $kernel->boot();
        $kernel->boot();

        self::assertSame(1, $calls);
        self::assertSame(State::BOOTED, $kernel->state());

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

        $kernel = new Application();
        $kernel->boot();
        $kernel->shutdown();

        self::assertSame(State::TERMINATED, $kernel->state());

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

        $kernel = new Application();
        $kernel->shutdown();

        self::assertSame(State::TERMINATED, $kernel->state());

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

        $calls = 0;
        $kernel = new Application();
        $kernel->registerTerminator(new BasicTerminator($calls));

        $kernel->boot();
        $kernel->shutdown();
        $kernel->shutdown();

        self::assertSame(1, $calls);
        self::assertSame(State::TERMINATED, $kernel->state());

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testGlobalAndApplicationBootstrappersRunInRegistrationOrder ():void {

        $events = [];
        $kernel = new Application();

        $kernel->registerBootstrapper(new EventBootTerm($events));

        $kernel->registerBootstrapper(new AppEventBootTerm($events));

        $kernel->boot();

        self::assertSame(['global', 'application'], $events);

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
    public function testGlobalAndApplicationTerminatorsRunInReverseRegistrationOrder ():void {

        $events = [];
        $kernel = new Application();

        $kernel->registerBootstrapper(new EventBootTerm($events));

        $kernel->registerBootstrapper(new AppEventBootTerm($events));

        $kernel->boot();
        $kernel->shutdown();

        self::assertSame(['global', 'application'], $events);

    }

}