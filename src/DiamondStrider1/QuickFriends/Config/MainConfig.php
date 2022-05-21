<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Config;

use DiamondStrider1\QuickFriends\Database\DatabaseConfig;
use DiamondStrider1\QuickFriends\Structures\WorldFilter;

final class MainConfig
{
    public function __construct(
        private int $maxFriendLimit,
        private WorldFilter $joinableWorlds,
        private DatabaseConfig $databaseConfig,
    ) {
    }

    public static function parse(Parser $parser): self
    {
        $maxFriendLimit = $parser->rInt('max-friend-limit');
        $joinableWorlds = WorldFilter::parse($parser->traverse('joinable-worlds'));
        $databaseConfig = DatabaseConfig::parse($parser->traverse('database'));

        $maxFriendLimit = $maxFriendLimit->take();
        if ($maxFriendLimit < -1 || 0 === $maxFriendLimit) {
            $parser->error('Element "max-friend-limit" must be a nonzero-positive integer or -1 (unlimited friends).');
        }

        $parser->check();

        return new self(
            $maxFriendLimit,
            $joinableWorlds->take(),
            $databaseConfig->take()
        );
    }

    public function maxFriendLimit(): int
    {
        return $this->maxFriendLimit;
    }

    public function joinableWorlds(): WorldFilter
    {
        return $this->joinableWorlds;
    }

    public function databaseConfig(): DatabaseConfig
    {
        return $this->databaseConfig;
    }
}
