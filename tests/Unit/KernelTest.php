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
use FireHub\Core\Boundary\Kernel as KernelBoundary;
use FireHub\Core\Meta\Enum\Kernel\State;
use FireHub\Kernel\Kernel;
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
 * ### Tests kernel lifecycle management
 * @since 1.0.0
 */
#[Small]
#[Group('kernel')]
#[CoversClass(Kernel::class)]
final class KernelTest extends FireHubTestCase {

    /**
     * ### Tests implementation of the kernel contract
     * @since 1.0.0
     *
     * @return void
     */
    public function testImplementsKernelContract ():void {

        $kernel = new Kernel;

        self::assertInstanceOf(KernelBoundary::class, $kernel);

    }

    /**
     * ### Tests the initial kernel state
     * @since 1.0.0
     *
     * @return void
     */
    public function testInitialState ():void {

        $kernel = new Kernel;

        self::assertSame(State::CREATED, $kernel->state());

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

        $kernel = new Kernel;

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->expects($this->once())
            ->method('bootstrap');

        $kernel->registerBootstrapper($bootstrapper);

        $kernel->boot();

        self::assertSame(State::BOOTED, $kernel->state());

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

        $kernel = new Kernel;

        $terminator = $this->createMock(Terminator::class);

        $terminator->expects($this->once())
            ->method('terminate');

        $kernel->registerTerminator($terminator);

        $kernel->shutdown();

        self::assertSame(State::TERMINATED, $kernel->state());

    }

    /**
     * ### Tests the complete kernel lifecycle
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testLifecycle ():void {

        $kernel = new Kernel;

        self::assertSame(State::CREATED, $kernel->state());

        $kernel->boot();

        self::assertSame(State::BOOTED, $kernel->state());

        $kernel->shutdown();

        self::assertSame(State::TERMINATED, $kernel->state());

    }

    /**
     * ### Tests bootstrapper execution order
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testBootstrapperOrder ():void {

        $kernel = new Kernel;

        $executed = [];

        foreach ([30, 10, 20] as $order) {

            $bootstrapper = $this->createMock(Bootstrapper::class);

            $bootstrapper->method('bootstrap')
                ->willReturnCallback(
                    static function () use (&$executed, $order):void {
                        $executed[] = $order;
                    }
                );

            $kernel->registerBootstrapper($bootstrapper, $order);

        }

        $kernel->boot();

        self::assertSame([10, 20, 30], $executed);

    }

    /**
     * ### Tests terminator execution order
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testTerminatorOrder ():void {

        $kernel = new Kernel;

        $executed = [];

        foreach ([30, 10, 20] as $order) {

            $terminator = $this->createMock(Terminator::class);

            $terminator->method('terminate')
                ->willReturnCallback(
                    static function () use (&$executed, $order):void {
                        $executed[] = $order;
                    }
                );

            $kernel->registerTerminator($terminator, $order);

        }

        $kernel->shutdown();

        self::assertSame([10, 20, 30], $executed);

    }

    /**
     * ### Tests repeated boot calls
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testBootRepeated ():void {

        $kernel = new Kernel;

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->expects($this->once())
            ->method('bootstrap');

        $kernel->registerBootstrapper($bootstrapper);

        $kernel->boot();
        $kernel->boot();

        self::assertSame(State::BOOTED, $kernel->state());

    }

    /**
     * ### Tests repeated shutdown calls
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testShutdownRepeated ():void {

        $kernel = new Kernel;

        $terminator = $this->createMock(Terminator::class);

        $terminator->expects($this->once())
            ->method('terminate');

        $kernel->registerTerminator($terminator);

        $kernel->shutdown();
        $kernel->shutdown();

        self::assertSame(State::TERMINATED, $kernel->state());

    }

    /**
     * ### Tests bootstrapper registration after boot
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testRegisterBootstrapperAfterBoot ():void {

        $kernel = new Kernel;

        $kernel->boot();

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $this->expectException(InvalidKernelStateException::class);

        $kernel->registerBootstrapper($bootstrapper);

    }

    /**
     * ### Tests terminator registration after boot
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelBootException
     *
     * @return void
     */
    public function testRegisterTerminatorAfterBoot ():void {

        $kernel = new Kernel;

        $kernel->boot();

        $terminator = $this->createMock(Terminator::class);

        $this->expectException(InvalidKernelStateException::class);

        $kernel->registerTerminator($terminator);

    }

    /**
     * ### Tests boot failure propagation
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testBootFailure ():void {

        $kernel = new Kernel;

        $failure = new \RuntimeException('Bootstrap failed.');

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->expects($this->once())
            ->method('bootstrap')
            ->willThrowException($failure);

        $kernel->registerBootstrapper($bootstrapper);

        try {

            $kernel->boot();

            self::fail('Expected KernelBootException was not thrown.');

        } catch (KernelBootException $exception) {

            self::assertSame($failure, $exception->getPrevious());
            self::assertSame(State::BOOT_FAILED, $kernel->state());

        }

    }

    /**
     * ### Tests shutdown failure propagation
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     *
     * @return void
     */
    public function testShutdownFailure ():void {

        $kernel = new Kernel;

        $failure = new \RuntimeException('Termination failed.');

        $terminator = $this->createMock(Terminator::class);

        $terminator->expects($this->once())
            ->method('terminate')
            ->willThrowException($failure);

        $kernel->registerTerminator($terminator);

        try {

            $kernel->shutdown();

            self::fail('Expected KernelShutdownException was not thrown.');

        } catch (KernelShutdownException $exception) {

            self::assertSame($failure, $exception->getPrevious());
            self::assertSame(State::SHUTDOWN_FAILED, $kernel->state());

        }

    }

    /**
     * ### Tests shutdown after boot failure
     * @since 1.0.0
     *
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\InvalidKernelStateException
     * @throws \FireHub\Kernel\Exception\KernelShutdownException
     *
     * @return void
     */
    public function testShutdownAfterBootFailure ():void {

        $kernel = new Kernel;

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->method('bootstrap')
            ->willThrowException(new \RuntimeException('Bootstrap failed.'));

        $kernel->registerBootstrapper($bootstrapper);

        try {

            $kernel->boot();

        } catch (KernelBootException) {

            self::assertSame(State::BOOT_FAILED, $kernel->state());

        }

        $kernel->shutdown();

        self::assertSame(State::TERMINATED, $kernel->state());

    }

    /**
     * ### Tests boot after shutdown is prohibited
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

        $kernel = new Kernel;

        $kernel->shutdown();

        $this->expectException(InvalidKernelStateException::class);

        $kernel->boot();

    }

}