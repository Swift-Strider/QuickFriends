<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

final class UserPreferences
{
    public const OS_VISIBILITY_EVERYONE = 0;
    public const OS_VISIBILITY_FRIENDS = 1;
    public const OS_VISIBILITY_NOBODY = 2;

    /**
     * @phpstan-param self::OS_VISIBILITY_* $osVisibility
     */
    public function __construct(
        private bool $prefersText,
        private int $osVisibility,
        private bool $muteFriendRequests
    ) {
    }

    public function prefersText(): bool
    {
        return $this->prefersText;
    }

    /**
     * @phpstan-return self::OS_VISIBILITY_*
     */
    public function osVisibility(): int
    {
        return $this->osVisibility;
    }

    public function muteFriendRequests(): bool
    {
        return $this->muteFriendRequests;
    }
}
