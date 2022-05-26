<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Event;

use DiamondStrider1\QuickFriends\Structures\Friendship;
use DiamondStrider1\QuickFriends\Structures\PlayerHandle;
use pocketmine\event\Event;

final class FriendRemovedEvent extends Event
{
    public function __construct(
        private Friendship $previousFriendship,
        private PlayerHandle $remover,
        private PlayerHandle $other,
    ) {
    }

    public function getPreviousFriendship(): Friendship
    {
        return $this->previousFriendship;
    }

    public function getRemover(): PlayerHandle
    {
        return $this->remover;
    }

    public function getOther(): PlayerHandle
    {
        return $this->other;
    }
}
