<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

final class Friendship
{
    public function __construct(
        private PlayerHandle $requester,
        private PlayerHandle $accepter,
        private int $creationTime,
    ) {
    }

    public function requester(): PlayerHandle
    {
        return $this->requester;
    }

    public function accepter(): PlayerHandle
    {
        return $this->accepter;
    }

    public function creationTime(): int
    {
        return $this->creationTime;
    }
}
