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
use FireHub\Kernel\Lifecycle\Manager;
use FireHub\Kernel\ {
    Bootstrapper, Terminator
};
use PHPUnit\Framework\Attributes\ {
    CoversClass, Group, Small
};
use Throwable;

/**
 * ### Test kernel lifecycle component execution
 * @since 1.0.0
 */
#[Small]
#[Group('lifecycle')]
#[CoversClass(Manager::class)]
final class ManagerTest extends FireHubTestCase {

    /**
     * ### Tests initialization without registered bootstrappers
     * @since 1.0.0
     *
     * @return void
     */
    public function testInitializeEmpty ():void {

        $manager = new Manager;

        $manager->initialize();

        self::assertTrue(true);

    }

    /**
     * ### Tests termination without registered terminators
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testTerminateEmpty ():void {

        $manager = new Manager;

        $manager->terminate();

        self::assertTrue(true);

    }

    /**
     * ### Tests bootstrapper registration and execution
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testRegisterBootstrapper ():void {

        $manager = new Manager;

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->expects($this->once())
            ->method('bootstrap');

        $manager->registerBootstrapper($bootstrapper, 0);

        $manager->initialize();

    }

    /**
     * ### Tests terminator registration and execution
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testRegisterTerminator ():void {

        $manager = new Manager;

        $terminator = $this->createMock(Terminator::class);

        $terminator->expects($this->once())
            ->method('terminate');

        $manager->registerTerminator($terminator, 0);

        $manager->terminate();

    }

    /**
     * ### Tests bootstrapper execution order
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testInitializeOrder ():void {

        $manager = new Manager;

        $executed = [];

        foreach ([30, 10, 20] as $order) {

            $bootstrapper = $this->createMock(Bootstrapper::class);

            $bootstrapper->method('bootstrap')
                ->willReturnCallback(
                    static function () use (&$executed, $order):void {
                        $executed[] = $order;
                    }
                );

            $manager->registerBootstrapper($bootstrapper, $order);

        }

        $manager->initialize();

        self::assertSame([10, 20, 30], $executed);

    }

    /**
     * ### Tests terminator execution order
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testTerminateOrder ():void {

        $manager = new Manager;

        $executed = [];

        foreach ([30, 10, 20] as $order) {

            $terminator = $this->createMock(Terminator::class);

            $terminator->method('terminate')
                ->willReturnCallback(
                    static function () use (&$executed, $order):void {
                        $executed[] = $order;
                    }
                );

            $manager->registerTerminator($terminator, $order);

        }

        $manager->terminate();

        self::assertSame([10, 20, 30], $executed);

    }

    /**
     * ### Tests initialization failure propagation
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testInitializeFailure ():void {

        $manager = new Manager;

        $failure = new \RuntimeException('Bootstrap failed.');

        $bootstrapper = $this->createMock(Bootstrapper::class);

        $bootstrapper->expects($this->once())
            ->method('bootstrap')
            ->willThrowException($failure);

        $manager->registerBootstrapper($bootstrapper, 0);

        try {

            $manager->initialize();

            self::fail('Expected bootstrap exception was not thrown.');

        } catch (\RuntimeException $exception) {

            self::assertSame($failure, $exception);

        }

    }

    /**
     * ### Tests termination failure propagation
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testTerminateFailure ():void {

        $manager = new Manager;

        $failure = new \RuntimeException('Termination failed.');

        $terminator = $this->createMock(Terminator::class);

        $terminator->expects($this->once())
            ->method('terminate')
            ->willThrowException($failure);

        $manager->registerTerminator($terminator, 0);

        try {

            $manager->terminate();

            self::fail('Expected termination exception was not thrown.');

        } catch (\RuntimeException $exception) {

            self::assertSame($failure, $exception);

        }

    }

}