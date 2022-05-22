<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

final class BlockRelation
{
    public function __construct(
        private string $player,
        private string $blocked,
        private int $creationTime,
    ) {
    }

    public function player(): string
    {
        return $this->player;
    }

    public function blocked(): string
    {
        return $this->blocked;
    }

    public function creationTime(): int
    {
        return $this->creationTime;
    }
}
