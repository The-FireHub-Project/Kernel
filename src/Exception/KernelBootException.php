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

namespace FireHub\Kernel\Exception;

use FireHub\Core\Exception\Runtime\KernelException;

/**
 * ### ### Represents a kernel boot failure
 *
 * Indicates that application kernel initialization could not be completed successfully.
 * @since 1.0.0
 */
final class KernelBootException extends KernelException {

    protected const string DEFAULT_MESSAGE = 'The kernel failed to boot';

}