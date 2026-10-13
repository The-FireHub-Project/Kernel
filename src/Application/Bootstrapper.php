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

namespace FireHub\Kernel\Application;

use FireHub\Core\Boundary\Kernel\Bootstrapper as BootstrapperBoundary;

/**
 * ### Defines an application kernel bootstrapper
 *
 * Establishes a bootstrapper contract for components that participate specifically in
 * application kernel initialization.
 *
 * Application bootstrappers provide application-specific initialization behavior without
 * requiring the kernel to depend on concrete component implementations.
 * @since 1.0.0
 */
interface Bootstrapper extends BootstrapperBoundary {}