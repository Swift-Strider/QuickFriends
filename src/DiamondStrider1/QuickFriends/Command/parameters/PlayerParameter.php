<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command\parameters;

use DiamondStrider1\QuickFriends\Command\CommandArgs;
use pocketmine\player\Player;
use pocketmine\Server;

class PlayerParameter extends CommandParameter
{
    public function get(CommandArgs $args): Player
    {
        $value = $args->take();
        if (null === $value) {
            $args->fail('A player\'s name was not given!');
        }
        $player = Server::getInstance()->getPlayerByPrefix($value);
        if (null === $player) {
            $args->fail("The player \"{$value}\" is not online!");
        }

        return $player;
    }

    public function getUsageType(): string
    {
        return 'player';
    }
}
