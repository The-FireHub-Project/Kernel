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
use PHPUnit\Framework\Attributes\ {
    CoversClass, Group, Small
};
use RuntimeException, Throwable;

/**
 * ### Test kernel lifecycle component execution
 * @since 1.0.0
 */
#[Small]
#[Group('lifecycle')]
#[CoversClass(Manager::class)]
final class ManagerTest extends FireHubTestCase {

    /**
     * ### Tests startup execution without callbacks
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testStartWithoutCallbacks ():void {

        $manager = new Manager;

        $manager->start();

        self::assertTrue(true);

    }

    /**
     * ### Tests shutdown execution without callbacks
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testStopWithoutCallbacks ():void {

        $manager = new Manager;

        $manager->stop();

        self::assertTrue(true);

    }

    /**
     * ### Tests startup ordering and stable priorities
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testStartupOrderAndStablePriority ():void {

        $manager = new Manager;
        $executed = [];

        foreach ([['third', 30], ['first', 10], ['second', 10], ['fourth', 20]] as [$name, $order]) {

            $manager->registerStartup(
                static function () use (&$executed, $name):void {

                    $executed[] = $name;

                },
                $order
            );

        }

        $manager->start();

        self::assertSame(
            ['first', 'second', 'fourth', 'third'],
            $executed
        );

    }

    /**
     * ### Tests shutdown ordering and stable priorities
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testShutdownOrderAndStablePriority ():void {

        $manager = new Manager;
        $executed = [];

        foreach ([['third', 30], ['first', 10], ['second', 10], ['fourth', 20]] as [$name, $order]) {

            $manager->registerShutdown(
                static function () use (&$executed, $name):void {

                    $executed[] = $name;

                },
                $order
            );

        }

        $manager->stop();

        self::assertSame(
            ['first', 'second', 'fourth', 'third'],
            $executed
        );

    }

    /**
     * ### Tests startup stops at the first failure
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testStartupStopsAtFirstFailure ():void {

        $manager = new Manager;

        $failure = new RuntimeException('Startup failed.');
        $executed = [];

        $manager->registerStartup(
            static function () use (&$executed):void {

                $executed[] = 'first';

            },
            0
        );

        $manager->registerStartup(
            static function () use ($failure):void {

                throw $failure;

            },
            1
        );

        $manager->registerStartup(
            static function () use (&$executed):void {

                $executed[] = 'never';

            },
            2
        );

        try {

            $manager->start();

            self::fail('Expected startup failure.');

        } catch (RuntimeException $exception) {

            self::assertSame($failure, $exception);
            self::assertSame(['first'], $executed);

        }

    }

    /**
     * ### Tests shutdown executes all callbacks after failures
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testShutdownRunsAllCallbacksAndRethrowsFirstFailure ():void {

        $manager = new Manager;

        $first = new RuntimeException('First failure.');
        $second = new RuntimeException('Second failure.');

        $executed = [];

        $manager->registerShutdown(
            static function () use (&$executed, $first):void {

                $executed[] = 'first';

                throw $first;

            },
            0
        );

        $manager->registerShutdown(
            static function () use (&$executed, $second):void {

                $executed[] = 'second';

                throw $second;

            },
            1
        );

        $manager->registerShutdown(
            static function () use (&$executed):void {

                $executed[] = 'third';

            },
            2
        );

        try {

            $manager->stop();

            self::fail('Expected shutdown failure.');

        } catch (RuntimeException $exception) {

            self::assertSame($first, $exception);

            self::assertSame(
                ['first', 'second', 'third'],
                $executed
            );

        }

    }

}