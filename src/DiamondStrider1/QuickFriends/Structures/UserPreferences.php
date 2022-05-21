<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

final class UserPreferences
{
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

    public function osVisibility(): int
    {
        return $this->osVisibility;
    }

    public function muteFriendRequests(): bool
    {
        return $this->muteFriendRequests;
    }
}
