<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

final class Friendship
{
    public function __construct(
        private string $requester,
        private string $accepter,
        private int $creationTime,
    ) {
    }

    public function requester(): string
    {
        return $this->requester;
    }

    public function accepter(): string
    {
        return $this->accepter;
    }

    public function creationTime(): int
    {
        return $this->creationTime;
    }
}
