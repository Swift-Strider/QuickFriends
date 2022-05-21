<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Modules;

use DomainException;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

/**
 * A store of interdependent modules.
 */
interface Context extends PluginOwned
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

    /**
     * Closes the context and all of the modules put inside of it.
     */
    public function close(): void;

    /**
     * Get the plugin that owns this context.
     */
    public function getOwningPlugin(): Plugin;
}
