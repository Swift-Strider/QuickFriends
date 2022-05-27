<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command\parameters;

use DiamondStrider1\QuickFriends\Command\CommandArgs;

class IntParameter extends CommandParameter
{
    public function get(CommandArgs $args): int
    {
        $value = $args->take();
        if (null === $value || !is_numeric($value)) {
            $args->fail('An integer was not given!');
        }

        return (int) $value;
    }

    public function getUsageType(): string
    {
        return 'int';
    }
}
