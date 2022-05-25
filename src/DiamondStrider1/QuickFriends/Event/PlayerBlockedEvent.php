<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Event;

use DiamondStrider1\QuickFriends\Structures\BlockRelation;
use pocketmine\event\Event;

final class PlayerBlockedEvent extends Event
{
    public function __construct(
        private BlockRelation $blockRelation,
        private bool $previouslyFriended,
    ) {
    }

    public function getBlockRelation(): BlockRelation
    {
        return $this->blockRelation;
    }

    public function werePreviouslyFriended(): bool
    {
        return $this->previouslyFriended;
    }
}
