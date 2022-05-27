<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command\parameters;

use DiamondStrider1\QuickFriends\Command\CommandArgs;

class FloatParameter extends CommandParameter
{
    public function get(CommandArgs $args): float
    {
        $value = $args->take();
        if (null === $value || !is_numeric($value)) {
            $args->fail('A float was not given!');
        }

        return (float) $value;
    }

    public function getUsageType(): string
    {
        return 'float';
    }
}
