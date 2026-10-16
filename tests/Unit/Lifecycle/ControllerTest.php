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
use FireHub\Kernel\ {
    Bootstrapper, Terminator
};
use FireHub\Kernel\Exception\ {
    InvalidKernelStateException, KernelBootException, KernelShutdownException
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

        $controller = new Controller;

        self::assertSame(State::CREATED, $controller->state());

    }

    /**
     * ### Tests bootstrapper registration
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testRegisterBootstrapper ():void {

        $controller = new Controller;

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->expects($this->once())
            ->method('bootstrap');

        $controller->registerBootstrapper($bootstrapper, 0);

        $controller->boot();

        self::assertSame(State::BOOTED, $controller->state());

    }

    /**
     * ### Tests terminator registration
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testRegisterTerminator ():void {

        $controller = new Controller;

        $terminator = $this->createMock(Terminator::class);

        $terminator->expects($this->once())
            ->method('terminate');

        $controller->registerTerminator($terminator, 0);

        $controller->shutdown();

        self::assertSame(State::TERMINATED, $controller->state());

    }

    /**
     * ### Tests successful boot
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testBoot ():void {

        $controller = new Controller;

        $controller->boot();

        self::assertSame(State::BOOTED, $controller->state());

    }

    /**
     * ### Tests repeated boot calls are ignored
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testBootRepeated ():void {

        $controller = new Controller;

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->expects($this->once())
            ->method('bootstrap');

        $controller->registerBootstrapper($bootstrapper, 0);

        $controller->boot();
        $controller->boot();

        self::assertSame(State::BOOTED, $controller->state());

    }

    /**
     * ### Tests boot failure handling
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testBootFailure ():void {

        $controller = new Controller;

        $failure = new \RuntimeException('Bootstrap failed.');

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->method('bootstrap')
            ->willThrowException($failure);

        $controller->registerBootstrapper($bootstrapper, 0);

        try {

            $controller->boot();

            self::fail('Expected KernelBootException was not thrown.');

        } catch (KernelBootException $exception) {

            self::assertSame($failure, $exception->getPrevious());
            self::assertSame(State::BOOT_FAILED, $controller->state());

        }

    }

    /**
     * ### Tests boot is prohibited after termination
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testBootAfterShutdown ():void {

        $controller = new Controller;

        $controller->shutdown();

        self::assertSame(State::TERMINATED, $controller->state());

        $this->expectException(InvalidKernelStateException::class);

        $controller->boot();

    }

    /**
     * ### Tests failed kernels cannot be rebooted
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testBootAfterFailure ():void {

        $controller = new Controller;

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->method('bootstrap')
            ->willThrowException(new \RuntimeException('Bootstrap failed.'));

        $controller->registerBootstrapper($bootstrapper, 0);

        try {

            $controller->boot();

        } catch (KernelBootException) {

            self::assertSame(State::BOOT_FAILED, $controller->state());

        }

        $this->expectException(InvalidKernelStateException::class);

        $controller->boot();

    }

    /**
     * ### Tests successful shutdown
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testShutdown ():void {

        $controller = new Controller;

        $controller->boot();
        $controller->shutdown();

        self::assertSame(State::TERMINATED, $controller->state());

    }

    /**
     * ### Tests shutdown before boot
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testShutdownBeforeBoot ():void {

        $controller = new Controller;

        $controller->shutdown();

        self::assertSame(State::TERMINATED, $controller->state());

    }

    /**
     * ### Tests repeated shutdown calls are ignored
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testShutdownRepeated ():void {

        $controller = new Controller;

        $terminator = $this->createMock(Terminator::class);

        $terminator->expects($this->once())
            ->method('terminate');

        $controller->registerTerminator($terminator, 0);

        $controller->shutdown();
        $controller->shutdown();

        self::assertSame(State::TERMINATED, $controller->state());

    }

    /**
     * ### Tests shutdown failure handling
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testShutdownFailure ():void {

        $controller = new Controller;

        $failure = new \RuntimeException('Termination failed.');

        $terminator = $this->createMock(Terminator::class);

        $terminator->method('terminate')
            ->willThrowException($failure);

        $controller->registerTerminator($terminator, 0);

        try {

            $controller->shutdown();

            self::fail('Expected KernelShutdownException was not thrown.');

        } catch (KernelShutdownException $exception) {

            self::assertSame($failure, $exception->getPrevious());
            self::assertSame(State::SHUTDOWN_FAILED, $controller->state());

        }

    }

    /**
     * ### Tests shutdown after a boot failure
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testShutdownAfterBootFailure ():void {

        $controller = new Controller;

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->method('bootstrap')
            ->willThrowException(new \RuntimeException('Bootstrap failed.'));

        $controller->registerBootstrapper($bootstrapper, 0);

        try {

            $controller->boot();

        } catch (KernelBootException) {

            self::assertSame(State::BOOT_FAILED, $controller->state());

        }

        $controller->shutdown();

        self::assertSame(State::TERMINATED, $controller->state());

    }

    /**
     * ### Tests bootstrapper registration is prohibited after boot
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testRegisterBootstrapperAfterBoot ():void {

        $controller = new Controller;

        $controller->boot();

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $this->expectException(InvalidKernelStateException::class);

        $controller->registerBootstrapper($bootstrapper, 0);

    }

    /**
     * ### Tests terminator registration is prohibited after boot
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testRegisterTerminatorAfterBoot ():void {

        $controller = new Controller;

        $controller->boot();

        $terminator = $this->createMock(Terminator::class);

        $this->expectException(InvalidKernelStateException::class);

        $controller->registerTerminator($terminator, 0);

    }

    /**
     * ### Tests registration is prohibited after termination
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testRegisterAfterShutdown ():void {

        $controller = new Controller;

        $controller->shutdown();

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $this->expectException(InvalidKernelStateException::class);

        $controller->registerBootstrapper($bootstrapper, 0);

    }

    /**
     * ### Tests lifecycle state during bootstrapping
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testStateDuringBoot ():void {

        $controller = new Controller;

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->method('bootstrap')
            ->willReturnCallback(
                static function () use ($controller):void {

                    self::assertSame(State::BOOTING, $controller->state());

                }
            );

        $controller->registerBootstrapper($bootstrapper, 0);

        $controller->boot();

        self::assertSame(State::BOOTED, $controller->state());

    }

    /**
     * ### Tests lifecycle state during termination
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testStateDuringShutdown ():void {

        $controller = new Controller;

        $terminator = $this->createMock(Terminator::class);

        $terminator->method('terminate')
            ->willReturnCallback(
                static function () use ($controller):void {

                    self::assertSame(State::SHUTTING_DOWN, $controller->state());

                }
            );

        $controller->registerTerminator($terminator, 0);

        $controller->shutdown();

        self::assertSame(State::TERMINATED, $controller->state());

    }

}