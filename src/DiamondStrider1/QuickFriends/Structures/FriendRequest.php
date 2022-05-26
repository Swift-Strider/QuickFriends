<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

final class FriendRequest
{
    public function __construct(
        private PlayerHandle $requester,
        private PlayerHandle $receiver,
        private float $creationTime,
    ) {
    }

    public function requester(): PlayerHandle
    {
        return $this->requester;
    }

    public function receiver(): PlayerHandle
    {
        return $this->receiver;
    }

    public function creationTime(): float
    {
        return $this->creationTime;
    }
}
