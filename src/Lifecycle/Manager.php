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

namespace FireHub\Kernel\Lifecycle;

use FireHub\Core\Boundary\Kernel\ {
    Bootstrapper, Terminator
};
use FireHub\Kernel\Bootstrapper\Sequence as BootstrapperSequence;
use FireHub\Kernel\Terminator\Sequence as TerminatorSequence;
use Throwable;

/**
 * ### Registers and executes kernel lifecycle components
 *
 * Executes bootstrappers and terminators according to their configured execution order. Components with equal order
 * values retain their registration order.
 *
 * State validation belongs to the lifecycle controller, not this execution manager.
 * @since 1.0.0
 */
final readonly class Manager {

    /**
     * ### Registered bootstrappers in registration order
     * @since 1.0.0
     */
    private BootstrapperSequence $bootstrap_sequence;

    /**
     * ### Registered terminators in shutdown order
     * @since 1.0.0
     */
    private TerminatorSequence $termination_sequence;

    /**
     * ### Constructor
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct () {

        $this->bootstrap_sequence = new BootstrapperSequence;

        $this->termination_sequence = new TerminatorSequence;

    }

    /**
     * ### Registers a bootstrapper
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Bootstrapper\Sequence::register() To register bootstrapper.
     *
     * @param \FireHub\Core\Boundary\Kernel\Bootstrapper $bootstrapper <p>
     * Bootstrapper to register.
     * </p>
     * @param int $order <p>
     * Execution order.
     * </p>
     *
     * @return void
     */
    public function registerBootstrapper (Bootstrapper $bootstrapper, int $order):void {

        $this->bootstrap_sequence->register($bootstrapper, $order);

    }

    /**
     * ### Registers a terminator
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Terminator\Sequence::register() To register terminator.
     *
     * @param \FireHub\Core\Boundary\Kernel\Terminator $terminator <p>
     * Terminator to register.
     * </p>
     * @param int $order <p>
     * Execution order.
     * </p>
     *
     * @return void
     */
    public function registerTerminator (Terminator $terminator, int $order):void {

        $this->termination_sequence->register($terminator, $order);

    }

    /**
     * ### Initializes registered components
     *
     * Executes the bootstrap sequence according to its configured execution order.
     *
     * Initialization stops immediately if a bootstrapper throws an exception.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Bootstrapper\Sequence::execute() To execute registered bootstrappers.
     *
     * @return void
     */
    public function initialize ():void {

        $this->bootstrap_sequence->execute();

    }

    /**
     * ### Terminates registered components
     *
     * Executes the termination sequence according to its configured execution order.
     *
     * The termination sequence is responsible for attempting all registered terminators and propagating execution
     * failures.
     * @since 1.0.0
     *
     * @uses \FireHub\Kernel\Terminator\Sequence::execute() To execute registered terminators.
     *
     * @throws Throwable If a terminator fails.
     *
     * @return void
     */
    public function terminate ():void {

        $this->termination_sequence->execute();

    }

}