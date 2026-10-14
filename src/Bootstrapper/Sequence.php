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

namespace FireHub\Kernel\Bootstrapper;

use FireHub\Core\Boundary\Kernel\Bootstrapper;
use FireHub\Runtime;

/**
 * ### Manages the kernel bootstrap sequence
 *
 * Registers and executes bootstrap components according to their configured execution order.
 *
 * Components with lower order values execute first. Components with equal order values retain their original
 * registration order.
 *
 * The sequence does not manage kernel lifecycle states.
 * @since 1.0.0
 */
final class Sequence {

    /**
     * ### Registered bootstrappers
     * @since 1.0.0
     *
     * @var list<array{bootstrapper: \FireHub\Core\Boundary\Kernel\Bootstrapper, order: int}>
     */
    private array $bootstrappers = [];

    /**
     * ### Registers a bootstrapper
     * @since 1.0.0
     *
     * @param \FireHub\Core\Boundary\Kernel\Bootstrapper $bootstrapper <p>
     * Bootstrap component.
     * </p>
     * @param int $order <p>
     * Execution order.
     * </p>
     *
     * @return void
     */
    public function register (Bootstrapper $bootstrapper, int $order):void {

        $this->bootstrappers[] = [
            'bootstrapper' => $bootstrapper,
            'order' => $order
        ];

    }

    /**
     * ### Executes the bootstrap sequence
     *
     * Executes registered bootstrappers in ascending order.
     *
     * Execution stops immediately if a bootstrapper throws an exception.
     * @since 1.0.0
     *
     * @uses \FireHub\Runtime\Arr\Ordering::sortBy() Sorts an array by a user-defined comparison function.
     * @uses \FireHub\Core\Boundary\Kernel\Bootstrapper::bootstrap() To bootstrap component.
     *
     * @return void
     */
    public function execute ():void {

        $entries = $this->bootstrappers;

        Runtime\Arr\Ordering::sortBy(
            $entries,
            static fn (array $a, array $b):int => $a['order'] <=> $b['order']
        );

        foreach ($entries as $entry)
            $entry['bootstrapper']->bootstrap();

    }

}