<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Modules;

use DomainException;
use Logger;
use pocketmine\plugin\Plugin;
use PrefixedLogger;

final class PluginContext implements Context
{
    public static function fromPlugin(Plugin $plugin): self
    {
        return new self(
            $plugin,
            new PrefixedLogger($plugin->getLogger(), 'PluginContext')
        );
    }

    public function __construct(
        private Plugin $plugin,
        private Logger $logger,
    ) {
    }

    /**
     * @var array<class-string<Module>, Module>
     */
    private array $modules = [];

    /**
     * @phpstan-template T of Module
     *
     * @phpstan-param class-string<T> $moduleClass
     * @phpstan-return ?T
     */
    public function tryGet(string $moduleClass): ?Module
    {
        $moduleName = ModuleUtils::getModuleName($moduleClass);
        $this->logger->debug('Try Get Module ('.$moduleName.')');

        /** @phpstan-var T $module */
        $module = $this->modules[$moduleClass] ?? null;

        return $module;
    }

    /**
     * Adds a new module to the context.
     *
     * @throws DomainException when there is already a module of the same class
     */
    public function put(Module $module): void
    {
        $moduleName = ModuleUtils::getModuleName($module::class);
        $this->logger->debug('Put Module ('.$moduleName.')');

        if (isset($this->modules[$module::class])) {
            throw new DomainException('Attempt to register two modules of the same class ('.$moduleName.')');
        }

        $this->modules[$module::class] = $module;
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->plugin;
    }
}
