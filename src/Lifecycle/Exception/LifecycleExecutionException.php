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

use FireHub\Core\Exception\RuntimeException;

/**
 * ### Represents a lifecycle execution failure
 *
 * Indicates that one or more registered lifecycle parts failed during startup or shutdown execution.
 *
 * The original failure is preserved as the previous exception, allowing lifecycle owners to expose domain-specific
 * exceptions without losing the underlying cause.
 * @since 1.0.0
 */
final class LifecycleExecutionException extends RuntimeException {

    protected const string DEFAULT_MESSAGE = 'One or more lifecycle parts failed during execution';

}