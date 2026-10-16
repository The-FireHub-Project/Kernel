<?php declare(strict_types = 1);

/**
 * This file is part of the FireHub Project ecosystem
 *
 * @author Danijel Galić <danijel.galic@outlook.com>
 * @copyright 2026-present The FireHub Project - All rights reserved
 * @license https://opensource.org/license/Apache-2-0 Apache License, Version 2.0
 *
 * @php-version >=7.0
 * @package Kernel\Tests
 */

namespace FireHub\Tests\Kernel\Stubs;

use FireHub\Kernel\Bootstrapper;

/**
 * ### Basic bootstrapper
 * @since 1.0.0
 */
class BasicBootstrapper implements Bootstrapper {

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct (
        private readonly mixed $data,
        private array &$executed
    ) {}

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     */
    public function bootstrap ():void {

        $this->executed[] = $this->data;

    }

}