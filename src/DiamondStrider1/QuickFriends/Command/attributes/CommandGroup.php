<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class CommandGroup
{
    public function __construct(
        private string $description,
        private string $permission,
    ) {
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPermission(): string
    {
        return $this->permission;
    }
}
