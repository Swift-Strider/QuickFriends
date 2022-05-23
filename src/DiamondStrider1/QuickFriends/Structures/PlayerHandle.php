<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

final class PlayerHandle
{
    public function __construct(
        private string $uuid,
        private string $username,
        private string $lastOs,
        private int $lastJoinTime,
    ) {
    }

    public function uuid(): string
    {
        return $this->uuid;
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
