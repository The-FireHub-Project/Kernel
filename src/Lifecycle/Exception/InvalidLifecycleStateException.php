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
 * ### Represents an invalid lifecycle state transition
 *
 * Indicates that a lifecycle operation was requested while the lifecycle
 * engine was in a state that does not permit the operation.
 *
 * This exception is independent of the lifecycle owner and may be translated
 * into a domain-specific exception by the component exposing the lifecycle.
 * @since 1.0.0
 */
class InvalidLifecycleStateException extends RuntimeException {

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     */
    protected const string DEFAULT_MESSAGE = 'The lifecycle is in an invalid state';

}