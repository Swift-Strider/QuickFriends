<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

final class PlayerData
{
    private UserPreferences $preferences;

    /**
     * @phpstan-param UserPreferences::OS_VISIBILITY_* $osVisibility
     */
    public function __construct(
        private string $uuid,
        private string $username,
        private string $lastOs,
        private int $lastJoinTime,
        bool $prefersText,
        int $osVisibility,
        bool $muteFriendRequests,
    ) {
        $this->preferences = new UserPreferences(
            $prefersText, $osVisibility, $muteFriendRequests
        );
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

    public function preferences(): UserPreferences
    {
        return $this->preferences;
    }
}
