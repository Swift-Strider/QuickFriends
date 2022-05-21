<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

final class PlayerData
{
    public function __construct(
        private string $username,
        private string $lastOs,
        private int $lastJoinTime
    ) {
    }

    public function username(): string
    {
        return $this->username;
    }

    public function lastOs(): string
    {
        return $this->lastOs;
    }

    public function lastJoinTime(): int
    {
        return $this->lastJoinTime;
    }
}
