<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Social;

use DiamondStrider1\QuickFriends\Config\ParsedValue;
use DiamondStrider1\QuickFriends\Config\Parser;
use DiamondStrider1\QuickFriends\Config\ParseStopException;
use DiamondStrider1\QuickFriends\Structures\WorldFilter;

final class SocialConfig
{
    public function __construct(
        private int $friendRequestDuration,
        private int $maxFriendLimit,
        private WorldFilter $joinableWorlds,
    ) {
    }

    /**
     * @phpstan-return ParsedValue<self>
     */
    public static function parse(Parser $parser): ParsedValue
    {
        $friendRequestDuration = $parser->rInt('friend-request-duration');
        $maxFriendLimit = $parser->rInt('max-friend-limit');
        $joinableWorlds = WorldFilter::parse($parser->traverse('joinable-worlds'));

        try {
            $maxFriendLimit = $maxFriendLimit->take();
            if ($maxFriendLimit < -1 || 0 === $maxFriendLimit) {
                $parser->error('Element "max-friend-limit" must be a nonzero-positive integer or -1 (unlimited friends).');
            }

            return ParsedValue::value(new self(
                $friendRequestDuration->take(),
                $maxFriendLimit,
                $joinableWorlds->take(),
            ));
        } catch (ParseStopException) {
            return ParsedValue::error('Could not parse Social Config.');
        }
    }

    public function friendRequestDuration(): int
    {
        return $this->friendRequestDuration;
    }

    public function maxFriendLimit(): int
    {
        return $this->maxFriendLimit;
    }

    public function joinableWorlds(): WorldFilter
    {
        return $this->joinableWorlds;
    }
}
