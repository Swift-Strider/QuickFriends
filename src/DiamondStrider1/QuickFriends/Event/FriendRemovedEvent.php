<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Event;

use DiamondStrider1\QuickFriends\Structures\Friendship;
use pocketmine\event\Event;

final class FriendRemovedEvent extends Event
{
    public function __construct(
        private Friendship $previousFriendship,
        private string $removerUuid,
        private string $otherUuid,
    ) {
    }

    public function getPreviousFriendship(): Friendship
    {
        return $this->previousFriendship;
    }

    public function getRemoverUuid(): string
    {
        return $this->removerUuid;
    }

    public function getOtherUuid(): string
    {
        return $this->otherUuid;
    }
}
