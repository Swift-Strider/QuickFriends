<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

final class FriendRequest
{
    public function __construct(
        private string $requester,
        private string $receiver,
        private float $creationTime,
        public bool $claimed,
    ) {
    }

    public function requester(): string
    {
        return $this->requester;
    }

    public function receiver(): string
    {
        return $this->receiver;
    }

    public function creationTime(): float
    {
        return $this->creationTime;
    }
}
