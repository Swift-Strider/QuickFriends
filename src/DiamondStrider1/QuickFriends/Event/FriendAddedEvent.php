<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Event;

use DiamondStrider1\QuickFriends\Structures\Friendship;
use pocketmine\event\Event;

final class FriendAddedEvent extends Event
{
    public function __construct(
        private Friendship $friendship,
    ) {
    }

    public function getFriendship(): Friendship
    {
        return $this->friendship;
    }
}
