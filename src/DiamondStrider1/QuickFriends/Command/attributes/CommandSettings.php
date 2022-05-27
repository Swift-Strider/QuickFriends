<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command\attributes;

use Attribute;
use pocketmine\lang\Translatable;

#[Attribute(Attribute::TARGET_METHOD)]
class CommandSettings
{
    /**
     * @param string[] $aliases
     */
    public function __construct(
        private string $name,
        private ?string $permission = null,
        private Translatable|string $description = '',
        private Translatable|string|null $usageMessage = null,
        private array $aliases = []
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getDescription(): Translatable|string
    {
        return $this->description;
    }

    public function getUsageMessage(): Translatable|string|null
    {
        return $this->usageMessage;
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }
}
