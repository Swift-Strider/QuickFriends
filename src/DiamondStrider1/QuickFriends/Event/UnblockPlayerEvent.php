<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Event;

use DiamondStrider1\QuickFriends\Structures\BlockRelation;
use pocketmine\event\Event;

final class UnblockPlayerEvent extends Event
{
    public function __construct(
        private BlockRelation $previousBlockRelation
    ) {
    }

    public function getPreviousBlockRelation(): BlockRelation
    {
        return $this->previousBlockRelation;
    }
}
