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

use FireHub\Kernel\Terminator;

/**
 * ### Basic terminator
 * @since 1.0.0
 */
class BasicTerminator implements Terminator {

    /**
     * @since 1.0.0
     */
    public function __construct (private int &$calls) {}

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     */
    public function terminate ():void {

        $this->calls++;

    }

}