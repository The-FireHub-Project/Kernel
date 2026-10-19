<?php declare(strict_types = 1);

/**
 * This file is part of the FireHub Project ecosystem
 *
 * @author Danijel Galić <danijel.galic@outlook.com>
 * @copyright 2026-present The FireHub Project - All rights reserved
 * @license https://opensource.org/license/Apache-2-0 Apache License, Version 2.0
 *
 * @php-version >=8.1
 * @package Kernel
 */

namespace FireHub\Kernel\Lifecycle\Exception;

use FireHub\Core\Exception\Runtime\InvalidArgumentException;

/**
 * ### Represents an invalid lifecycle phase
 * @since 1.0.0
 */
final class DistinctEnumCaseException extends InvalidArgumentException {

    protected const string DEFAULT_MESSAGE = 'Each lifecycle phase must have a distinct enumeration case';

}