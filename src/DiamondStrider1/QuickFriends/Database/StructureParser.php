<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Database;

use DiamondStrider1\QuickFriends\Structures\BlockRelation;
use DiamondStrider1\QuickFriends\Structures\Friendship;
use DiamondStrider1\QuickFriends\Structures\PlayerData;
use DiamondStrider1\QuickFriends\Structures\PlayerHandle;
use InvalidArgumentException;

final class StructureParser
{
    public static function parseFriendship(mixed $data): Friendship
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Parameter $data is not an array!');
        }

        $creationTime = $data['creation_time'];
        if (is_string($creationTime)) {
            $creationTime = strtotime($creationTime);
        }

        $requesterLastJoinTime = $data['requester_last_join_time'];
        if (is_string($requesterLastJoinTime)) {
            $requesterLastJoinTime = strtotime($requesterLastJoinTime);
        }

        $accepterLastJoinTime = $data['accepter_last_join_time'];
        if (is_string($accepterLastJoinTime)) {
            $accepterLastJoinTime = strtotime($accepterLastJoinTime);
        }

        return new Friendship(
            new PlayerHandle(
                $data['requester_uuid'],
                $data['requester_username'],
                $data['requester_last_os'],
                $requesterLastJoinTime,
            ),
            new PlayerHandle(
                $data['accepter_uuid'],
                $data['accepter_username'],
                $data['accepter_last_os'],
                $accepterLastJoinTime,
            ),
            $creationTime,
        );
    }

    public static function parseBlockRelation(mixed $data): BlockRelation
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Parameter $data is not an array!');
        }

        $creationTime = $data['creation_time'];
        if (is_string($creationTime)) {
            $creationTime = strtotime($creationTime);
        }

        $playerLastJoinTime = $data['player_last_join_time'];
        if (is_string($playerLastJoinTime)) {
            $playerLastJoinTime = strtotime($playerLastJoinTime);
        }

        $blockedLastJoinTime = $data['blocked_last_join_time'];
        if (is_string($blockedLastJoinTime)) {
            $blockedLastJoinTime = strtotime($blockedLastJoinTime);
        }

        return new BlockRelation(
            new PlayerHandle(
                $data['player_uuid'],
                $data['player_username'],
                $data['player_last_os'],
                $playerLastJoinTime,
            ),
            new PlayerHandle(
                $data['blocked_uuid'],
                $data['blocked_username'],
                $data['blocked_last_os'],
                $blockedLastJoinTime,
            ),
            $creationTime,
        );
    }

    public static function parsePlayerData(mixed $data): PlayerData
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Parameter $data is not an array!');
        }

        $lastJoinTime = $data['last_join_time'];
        if (is_string($lastJoinTime)) {
            $lastJoinTime = strtotime($lastJoinTime);
        }

        return new PlayerData(
            $data['uuid'],
            $data['username'],
            $data['last_os'],
            $lastJoinTime,
            (bool) $data['prefers_text'],
            $data['os_visibility'],
            (bool) $data['mute_friend_requests'],
        );
    }
}
