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

namespace FireHub\Kernel\Lifecycle\Dependency;

/**
 * ### Represents an active lifecycle dependency
 *
 * Holds an active dependency registration until explicitly released.
 *
 * Releasing a lease is idempotent.
 * @since 1.0.0
 *
 * @internal
 */
final class Lease {

    /**
     * ### Whether the lease has been released
     * @since 1.0.0
     */
    private bool $released = false;

    /**
     * ### Constructor
     * @since 1.0.0
     *
     * @param int $id <p>
     * Dependency identifier.
     * </p>
     * @param \FireHub\Kernel\Lifecycle\Dependency\Registry $registry <p>
     * Owning dependency registry.
     * </p>
     *
     * @void
     *
     * @internal
     */
    public function __construct (
        private readonly int $id,
        private readonly Registry $registry
    ) {}

    /**
     * ### Releases the dependency
     *
     * Removes this dependency from its owning registry.
     *
     * Repeated calls have no effect.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Lifecycle\Dependency\Registry::release() To release the dependency.
     *
     * @return void
     */
    public function release ():void {

        if ($this->released) return;

        $this->registry->release($this->id);

        $this->released = true;

    }

    /**
     * ### Checks whether the lease has been released
     * @since 1.0.0
     *
     * @return bool True when released.
     */
    public function isReleased ():bool {

        return $this->released;

    }

}