<?php declare(strict_types = 1);

/**
 * This file is part of the FireHub Project ecosystem
 *
 * @author Danijel Galić <danijel.galic@outlook.com>
 * @copyright 2026-present The FireHub Project - All rights reserved
 * @license https://opensource.org/license/Apache-2-0 Apache License, Version 2.0
 *
 * @php-version >=8.5
 * @package Kernel
 */

namespace FireHub\Kernel\Lifecycle\Dependency;

use FireHub\Runtime;
use FireHub\Kernel\Lifecycle\Exception\ActiveLifecycleDependenciesException;

/**
 * ### Tracks active lifecycle dependencies
 *
 * Maintains a registry of components that depend on an active lifecycle.
 *
 * Each registered dependency receives a lease that must be released when the dependent component has completed its
 * lifecycle.
 *
 * The registry prevents the owning lifecycle from terminating while active dependencies remain.
 * @since 1.0.0
 *
 * @internal
 */
final class Registry {

    /**
     * ### Active dependency leases
     * @since 1.0.0
     *
     * @var array<int, \FireHub\Kernel\Lifecycle\Dependency\Lease>
     */
    private array $leases = [];

    /**
     * ### Next lease identifier
     * @since 1.0.0
     */
    private int $next_id = 0;

    /**
     * ### Acquires a dependency lease
     *
     * Registers an active dependency and returns a lease representing its ownership.
     * @since 1.0.0
     *
     * @return \FireHub\Kernel\Lifecycle\Dependency\Lease Acquired dependency lease.
     */
    public function acquire ():Lease {

        $id = ++$this->next_id;

        return $this->leases[$id] = new Lease(
            $id,
            $this
        );

    }

    /**
     * ### Releases a dependency lease
     *
     * Removes the specified lease from the active dependency registry.
     * @since 1.0.0
     *
     * @param int $id Dependency lease identifier.
     *
     * @return void
     */
    public function release (int $id):void {

        unset($this->leases[$id]);

    }

    /**
     * ### Checks for active dependencies
     * @since 1.0.0
     *
     * @return bool True when at least one dependency remains active.
     */
    public function hasActive ():bool {

        return $this->leases !== [];

    }

    /**
     * ### Gets the number of active dependencies
     * @since 1.0.0
     *
     * @uses \FireHub\Runtime\Arr\Inspection::count() To count the number of active dependencies.
     *
     * @return int Number of active dependencies.
     */
    public function count ():int {

        return Runtime\Arr\Inspection::count($this->leases);

    }

    /**
     * ### Ensures all dependencies have been released
     *
     * Prevents lifecycle termination while dependent components remain active.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Dependency\Registry::hasActive() To check for active dependencies.
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\ActiveLifecycleDependenciesException If active dependencies remain.
     *
     * @return void
     */
    public function assertEmpty ():void {

        if ($this->hasActive())
            throw new ActiveLifecycleDependenciesException(
                'Cannot terminate lifecycle while dependent components are active.'
            );

    }

}