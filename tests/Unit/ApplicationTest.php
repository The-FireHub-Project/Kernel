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
use FireHub\Kernel\Application\ {
    Context, Environment, Name
};
use FireHub\Kernel\Exception\InvalidKernelStateException;
use FireHub\Tests\Kernel\Stubs\ {
    AppEventBootTerm, BasicBootstrapper, BasicTerminator, EventBootTerm
};
use PHPUnit\Framework\Attributes\ {
    CoversClass, Group, Small
};

/**
 * ### Tests application kernel lifecycle management
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

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

        self::assertSame(State::CREATED, $kernel->state());

    }

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function testContext ():void {

        $name = new Name('FireHub');

        $context = new Context(
            name: $name,
            environment: Environment::TESTING
        );

        $kernel = new Application($context);

        self::assertSame($context, $kernel->context());

        self::assertSame($name, $kernel->context()->name);
        self::assertSame('FireHub', $kernel->context()->name->value());
        self::assertSame(Environment::TESTING, $kernel->context()->environment);

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

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

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

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

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

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

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

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

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

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

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

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

        $kernel->registerBootstrapper(new EventBootTerm($events));
        $kernel->registerBootstrapper(new AppEventBootTerm($events));

        $kernel->boot();

        self::assertSame([
            'global',
            'application'
        ], $events);

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
    public function testGlobalAndApplicationTerminatorsRunInExecutionOrder ():void {

        $events = [];

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

        $kernel->registerTerminator(new EventBootTerm($events), 100);
        $kernel->registerTerminator(new AppEventBootTerm($events), -100);

        $kernel->boot();
        $kernel->shutdown();

        self::assertSame([
            'application',
            'global'
        ], $events);

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
    public function testCompleteLifecycle ():void {

        $events = [];

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

        $global = new EventBootTerm($events);
        $application = new AppEventBootTerm($events);

        $kernel->registerBootstrapper($global, -100);
        $kernel->registerBootstrapper($application, 100);

        $kernel->registerTerminator($global, 100);
        $kernel->registerTerminator($application, -100);

        self::assertSame(State::CREATED, $kernel->state());

        $kernel->boot();

        self::assertSame(State::BOOTED, $kernel->state());

        $kernel->shutdown();

        self::assertSame(State::TERMINATED, $kernel->state());

        self::assertSame([
            'global',
            'application',
            'application',
            'global'
        ], $events);

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     */
    public function testDefaultExecutionOrder ():void {

        $events = [];

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

        $kernel->registerBootstrapper(new EventBootTerm($events));
        $kernel->registerBootstrapper(new AppEventBootTerm($events));

        $kernel->boot();

        self::assertSame([
            'global',
            'application'
        ], $events);

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

        $calls = 0;

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

        $kernel->boot();

        $this->expectException(InvalidKernelStateException::class);

        $kernel->registerBootstrapper(new BasicBootstrapper($calls));

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

        $calls = 0;

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

        $kernel->boot();

        $this->expectException(InvalidKernelStateException::class);

        $kernel->registerTerminator(new BasicTerminator($calls));

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testCannotRegisterBootstrapperAfterShutdown ():void {

        $calls = 0;

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

        $kernel->shutdown();

        $this->expectException(InvalidKernelStateException::class);

        $kernel->registerBootstrapper(new BasicBootstrapper($calls));

    }

    /**
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testCannotRegisterTerminatorAfterShutdown ():void {

        $calls = 0;

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

        $kernel->shutdown();

        $this->expectException(InvalidKernelStateException::class);

        $kernel->registerTerminator(new BasicTerminator($calls));

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

        $kernel = new Application(
            new Context(new Name('FireHub'), Environment::TESTING)
        );

        $kernel->shutdown();

        $this->expectException(InvalidKernelStateException::class);

        $kernel->boot();

    }

}