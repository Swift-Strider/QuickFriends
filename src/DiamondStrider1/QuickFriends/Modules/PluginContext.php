<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Modules;

use pocketmine\plugin\Plugin;
use DomainException;
use Logger;
use PrefixedLogger;

class PluginContext implements Context
{
    public static function fromPlugin(Plugin $plugin): self {
        return new self(
            new PrefixedLogger($plugin->getLogger(), "PluginContext")
        );
    }

    public function __construct(
        public Logger $logger
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
        $this->logger->debug("Try Get Module (" . $moduleClass . ")");

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
        $this->logger->debug("Put Module (" . $module::class . ")");

        if (isset($this->modules[$module::class])) {
            throw new DomainException("Attempt to register two modules of the same class (" . $module::class . ")");
        }

        $this->modules[$module::class] = $module;
    }
}
