<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command;

use DiamondStrider1\QuickFriends\Command\attributes\CommandGroup;

interface OverloadedCommand
{
    public function getCommandGroup(): CommandGroup;

    /** @return CommandOverload[] */
    public function getOverloads(): array;
}
