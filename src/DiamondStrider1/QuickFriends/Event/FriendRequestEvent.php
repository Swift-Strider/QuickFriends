<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Event;

use DiamondStrider1\QuickFriends\Structures\FriendRequest;
use pocketmine\event\Event;

final class FriendRequestEvent extends Event
{
    public function __construct(
        private FriendRequest $request,
        private bool $muted,
    ) {
    }

    public function getRequest(): FriendRequest
    {
        return $this->request;
    }

    public function isMuted(): bool
    {
        return $this->muted;
    }
}
