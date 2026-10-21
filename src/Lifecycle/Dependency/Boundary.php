<?php declare(strict_types = 1);

/**
 * This file is part of the FireHub Project ecosystem
 *
 * @author Danijel Galić <danijel.galic@outlook.com>
 * @copyright 2026-present The FireHub Project - All rights reserved
 * @license https://opensource.org/license/Apache-2-0 Apache License, Version 2.0
 *
 * @php-version >=8.2
 * @package Kernel
 */

namespace FireHub\Kernel\Lifecycle\Dependency;

use FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException;
use Closure;

/**
 * ### Provides lifecycle dependency access
 *
 * Exposes a restricted interface for acquiring dependencies on an active lifecycle without exposing its execution or
 * state mutation operations.
 *
 * Dependent lifecycle engines use this boundary to acquire ownership of their parent lifecycle during execution.
 * @since 1.0.0
 */
final readonly class Boundary {

    /**
     * ### Constructor
     * @since 1.0.0
     *
     * @param Closure():bool $isActive <p>
     * Parent lifecycle availability check.
     * </p>
     * @param \FireHub\Kernel\Lifecycle\Dependency\Registry $registry <p>
     * Parent lifecycle dependency registry.
     * </p>
     *
     * @return void
     */
    public function __construct (
        private Closure $isActive,
        private Registry $registry
    ) {}

    /**
     * ### Acquires a lifecycle dependency
     *
     * Registers a dependency only when the owning lifecycle is active.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Dependency\Boundary::isActive() To check the parent lifecycle availability.
     * @uses \FireHub\Kernel\Lifecycle\Dependency\Registry::acquire() To acquire a dependency lease.
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\InvalidLifecycleStateException If the parent lifecycle is not active.
     *
     * @return \FireHub\Kernel\Lifecycle\Dependency\Lease Acquired dependency lease.
     */
    public function acquire ():Lease {

        if (!($this->isActive)())
            throw new InvalidLifecycleStateException(
                'The parent lifecycle must be active before starting a dependent lifecycle.'
            );

        return $this->registry->acquire();

    }

}