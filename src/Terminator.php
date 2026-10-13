<?php declare(strict_types = 1);

/**
 * This file is part of the FireHub Project ecosystem
 *
 * @author Danijel Galić <danijel.galic@outlook.com>
 * @copyright 2026-present The FireHub Project - All rights reserved
 * @license https://opensource.org/license/Apache-2-0 Apache License, Version 2.0
 *
 * @php-version >=7.0
 * @package Kernel
 */

namespace FireHub\Kernel;

use FireHub\Core\Boundary\Kernel\Terminator as TerminatorBoundary;

/**
 * ### Defines a global kernel terminator
 *
 * Establishes a termination contract for components that can participate in the shutdown
 * of any compatible kernel.
 *
 * Global terminators are independent of a specific kernel type and can be shared across
 * application, testing, worker, and other kernel implementations.
 * @since 1.0.0
 */
interface Terminator extends TerminatorBoundary {}