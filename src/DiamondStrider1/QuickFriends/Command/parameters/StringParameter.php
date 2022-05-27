<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command\parameters;

use DiamondStrider1\QuickFriends\Command\CommandArgs;

class StringParameter extends CommandParameter
{
    public function get(CommandArgs $args): string
    {
        $value = $args->take();
        if (null === $value) {
            $args->fail('A string was not given!');
        }

        return $value;
    }

    public function getUsageType(): string
    {
        return 'string';
    }
}
