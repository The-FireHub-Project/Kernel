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

namespace FireHub\Tests\Kernel\Stubs;

/**
 * ### Lifecycle states
 * @since 1.0.0
 */
enum State {

    case CREATED;
    case STARTING;
    case RUNNING;
    case STOPPED;
    case FAILED;

}