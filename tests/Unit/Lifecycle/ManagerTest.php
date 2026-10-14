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
use FireHub\Tests\Kernel\Stubs\ {
    AppEventBootTerm, BasicBootstrapper, BasicTerminator, EventBootTerm
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
     * @since 1.0.0
     *
     * @return void
     */
    public function testInitializeWithoutBootstrappers ():void {

        $manager = new Manager();

        $manager->initialize();

        self::assertTrue(true);

    }

    /**
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testTerminateWithoutTerminators ():void {

        $manager = new Manager();

        $manager->terminate();

        self::assertTrue(true);

    }

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function testInitializeExecutesBootstrapper ():void {

        $manager = new Manager();
        $calls = 0;

        $manager->registerBootstrapper(new BasicBootstrapper($calls), 0);

        $manager->initialize();

        self::assertSame(1, $calls);

    }

    /**
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testTerminateExecutesTerminator ():void {

        $manager = new Manager();
        $calls = 0;

        $manager->registerTerminator(new BasicTerminator($calls), 0);

        $manager->terminate();

        self::assertSame(1, $calls);

    }

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function testInitializeExecutesAllBootstrappers ():void {

        $manager = new Manager();
        $calls = 0;

        $manager->registerBootstrapper(new BasicBootstrapper($calls), 0);
        $manager->registerBootstrapper(new BasicBootstrapper($calls), 0);
        $manager->registerBootstrapper(new BasicBootstrapper($calls), 0);

        $manager->initialize();

        self::assertSame(3, $calls);

    }

    /**
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testTerminateExecutesAllTerminators ():void {

        $manager = new Manager();
        $calls = 0;

        $manager->registerTerminator(new BasicTerminator($calls), 0);
        $manager->registerTerminator(new BasicTerminator($calls), 0);
        $manager->registerTerminator(new BasicTerminator($calls), 0);

        $manager->terminate();

        self::assertSame(3, $calls);

    }

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function testInitializePreservesRegistrationOrder ():void {

        $manager = new Manager();
        $events = [];

        $manager->registerBootstrapper(new EventBootTerm($events), 0);
        $manager->registerBootstrapper(new AppEventBootTerm($events), 0);

        $manager->initialize();

        self::assertSame([
            'global',
            'application'
        ], $events);

    }

    /**
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testTerminateUsesReverseRegistrationOrder ():void {

        $manager = new Manager();
        $events = [];

        $manager->registerTerminator(new EventBootTerm($events), 0);
        $manager->registerTerminator(new AppEventBootTerm($events), 0);

        $manager->terminate();

        self::assertSame([
            'global',
            'application'
        ], $events);

    }

    /**
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testInitializeUsesExecutionOrder ():void {

        $manager = new Manager();
        $events = [];

        $manager->registerBootstrapper(new EventBootTerm($events), 100);
        $manager->registerBootstrapper(new AppEventBootTerm($events), -100);

        $manager->initialize();

        self::assertSame([
            'application',
            'global'
        ], $events);

    }

    /**
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testTerminateUsesExecutionOrder ():void {

        $manager = new Manager();
        $events = [];

        $manager->registerTerminator(new EventBootTerm($events), 100);
        $manager->registerTerminator(new AppEventBootTerm($events), -100);

        $manager->terminate();

        self::assertSame([
            'application',
            'global'
        ], $events);

    }

    /**
     * @since 1.0.0
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testInitializeAndTerminate ():void {

        $manager = new Manager();
        $events = [];

        $global = new EventBootTerm($events);
        $application = new AppEventBootTerm($events);

        $manager->registerBootstrapper($global, -100);
        $manager->registerBootstrapper($application, 100);

        $manager->registerTerminator($global, 100);
        $manager->registerTerminator($application, -100);

        $manager->initialize();
        $manager->terminate();

        self::assertSame([
            'global',
            'application',
            'application',
            'global'
        ], $events);

    }

}