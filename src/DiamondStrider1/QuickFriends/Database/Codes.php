<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Database;

final class Codes
{
    public const REQUEST_NOTIFY = 0;
    public const REQUEST_MUTE = 1;
    public const REQUEST_BLOCKED = 2;
    public const REQUEST_BLOCKED_BY = 3;
    public const REQUEST_ALREADY_FRIENDS = 4;
    public const REQUEST_LIMIT_REACHED = 5;

    public const FRIEND_NOW_FRIENDS = 0;
    public const FRIEND_ALREADY_FRIENDS = 1;
    public const FRIEND_LIMIT_REACHED = 2;

    public const UNFRIEND_NOT_FRIENDS = 0;

    public const BLOCK_NOW_BLOCKED = 0;
    public const BLOCK_ALSO_UNFRIENDED = 1;
    public const BLOCK_ALREADY_BLOCKED = 2;

    public const UNBLOCK_NOT_BLOCKED = 0;

    public static function validRequest(int $code): bool
    {
        return $code >= 0 && $code <= 5;
    }

    public static function validFriend(int $code): bool
    {
        return $code >= 0 && $code <= 3;
    }

    public static function validUnfriend(int $code): bool
    {
        return 0 === $code;
    }

    public static function validBlock(int $code): bool
    {
        return $code >= 0 && $code <= 3;
    }

    public static function validUnblock(int $code): bool
    {
        return 0 === $code;
    }
}
