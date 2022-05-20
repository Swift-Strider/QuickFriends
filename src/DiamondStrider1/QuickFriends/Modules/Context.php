<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Modules;

use DomainException;

/**
 * A store of interdependent modules.
 */
interface Context
{
    /**
     * @phpstan-template T of Module
     *
     * @phpstan-param class-string<T> $moduleClass
     * @phpstan-return ?T
     */
    public function tryGet(string $moduleClass): ?Module;

    /**
     * Adds a new module to the context.
     *
     * @throws DomainException when there is already a module of the same class
     */
    public function put(Module $module): void;
}
