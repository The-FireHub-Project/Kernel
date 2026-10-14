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

namespace FireHub\Tests\Kernel\Unit\Application;

use FireHub\Testing\FireHubTestCase;
use FireHub\Kernel\Application\ {
    Context, Environment, Name
};
use PHPUnit\Framework\Attributes\ {
    CoversClass, Group, Small
};

/**
 * ### Tests immutable application execution context
 * @since 1.0.0
 */
#[Small]
#[Group('application')]
#[CoversClass(Context::class)]
final class ApplicationContextTest extends FireHubTestCase {

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function testProperties ():void {

        $name = new Name('FireHub');

        $context = new Context(
            name: $name,
            environment: Environment::TESTING
        );

        self::assertSame($name, $context->name);
        self::assertSame(Environment::TESTING, $context->environment);

    }

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function testValue ():void {

        $name = new Name('FireHub');

        $context = new Context(
            name: $name,
            environment: Environment::DEVELOPMENT
        );

        self::assertSame([
            'name' => $name,
            'environment' => Environment::DEVELOPMENT
        ], $context->value());

    }

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function testEquals ():void {

        $first = new Context(
            new Name('FireHub'),
            Environment::TESTING
        );

        $second = new Context(
            new Name('FireHub'),
            Environment::TESTING
        );

        self::assertTrue($first->equals($second));

    }

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function testDifferentNamesAreNotEqual ():void {

        $first = new Context(
            new Name('FireHub'),
            Environment::TESTING
        );

        $second = new Context(
            new Name('OtherApplication'),
            Environment::TESTING
        );

        self::assertFalse($first->equals($second));

    }

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function testDifferentEnvironmentsAreNotEqual ():void {

        $first = new Context(
            new Name('FireHub'),
            Environment::TESTING
        );

        $second = new Context(
            new Name('FireHub'),
            Environment::PRODUCTION
        );

        self::assertFalse($first->equals($second));

    }

}