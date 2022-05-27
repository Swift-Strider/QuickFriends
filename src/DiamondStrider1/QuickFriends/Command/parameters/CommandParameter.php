<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command\parameters;

use DiamondStrider1\QuickFriends\Command\CommandArgs;
use DiamondStrider1\QuickFriends\Command\ValidationException;

abstract class CommandParameter
{
    public function __construct(
        private bool $optional
    ) {
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    /**
     * @throws ValidationException
     */
    abstract public function get(CommandArgs $args): mixed;

    /**
     * The type used in default usage.
     * Ex: With `/cmd <param: int>`, getUsageType returned "int".
     */
    abstract public function getUsageType(): string;
}
