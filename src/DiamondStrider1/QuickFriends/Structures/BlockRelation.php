<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

final class BlockRelation
{
    public function __construct(
        private PlayerHandle $player,
        private PlayerHandle $blocked,
        private int $creationTime,
    ) {
    }

    public function player(): PlayerHandle
    {
        return $this->player;
    }

    public function blocked(): PlayerHandle
    {
        return $this->blocked;
    }

    public function creationTime(): int
    {
        return $this->creationTime;
    }
}
