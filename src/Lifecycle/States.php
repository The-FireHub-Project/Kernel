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

namespace FireHub\Kernel\Lifecycle;

use FireHub\Kernel\Lifecycle\Exception\DistinctEnumCaseException;
use UnitEnum;

/**
 * ### Defines lifecycle state mappings
 *
 * Associates the fundamental phases of a lifecycle with the states provided by a specific lifecycle owner.
 *
 * The mapping allows the lifecycle engine to operate independently of concrete state names, namespaces, and
 * domain-specific terminology.
 *
 * Each lifecycle owner supplies its own enumeration values when constructing the lifecycle engine. The engine then
 * uses these values directly without converting or mapping states during execution.
 *
 * The supplied states must represent distinct lifecycle phases. Each lifecycle owner normally supplies cases
 * from a single enumeration.
 * @since 1.0.0
 *
 * @template TState of UnitEnum
 */
final readonly class States {

    /**
     * ### Constructor
     *
     * Defines the states associated with the seven fundamental lifecycle phases.
     * @since 1.0.0
     *
     * @param TState $created <p>
     * Initial lifecycle state.
     * </p>
     * @param TState $starting <p>
     * State while startup components are executing.
     * </p>
     * @param TState $started <p>
     * State after successful startup.
     * </p>
     * @param TState $stopping <p>
     * State while shutdown components are executing.
     * </p>
     * @param TState $stopped <p>
     * State after successful shutdown.
     * </p>
     * @param TState $start_failed <p>
     * State after unsuccessful startup.
     * </p>
     * @param TState $stop_failed <p>
     * State after unsuccessful shutdown.
     * </p>
     *
     * @throws \FireHub\Kernel\Lifecycle\Exception\DistinctEnumCaseException If enum case is assigned to multiple
     * phases.
     *
     * @return void
     */
    public function __construct (
        public UnitEnum $created,
        public UnitEnum $starting,
        public UnitEnum $started,
        public UnitEnum $stopping,
        public UnitEnum $stopped,
        public UnitEnum $start_failed,
        public UnitEnum $stop_failed
    ) {

        $states = [
            $created,
            $starting,
            $started,
            $stopping,
            $stopped,
            $start_failed,
            $stop_failed
        ];

        $names = [];

        foreach ($states as $state) {

            $key = $state::class.'::'.$state->name;

            if (isset($names[$key]))
                throw new DistinctEnumCaseException;

            $names[$key] = true;

        }

    }

}