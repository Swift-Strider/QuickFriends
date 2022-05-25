<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Database;

use DiamondStrider1\QuickFriends\Structures\BlockRelation;
use DiamondStrider1\QuickFriends\Structures\Friendship;
use DiamondStrider1\QuickFriends\Structures\PlayerData;
use DiamondStrider1\QuickFriends\Structures\PlayerHandle;
use DiamondStrider1\QuickFriends\Structures\UserPreferences;
use Generator;
use LogicException;
use poggit\libasynql\DataConnector;
use SOFe\AwaitGenerator\Await;

final class MySqlDatabase implements Database
{
    public function __construct(
        private DataConnector $db
    ) {
    }

    public function initialize(): Generator
    {
        yield from Await::promise(function ($resolve, $reject) {
            $this->db->executeGeneric('quickfriends.init', [], $resolve, $reject);
        });
    }

    public function getPlayerData(
        string $player
    ): Generator {
        $rows = yield from Await::promise(function ($resolve, $reject) use ($player) {
            $this->db->executeSelect('quickfriends.get_player_data', [
                'uuid' => $player,
            ], $resolve, $reject);
        });

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        $row = $rows[0] ?? null;
        if (null === $row) {
            return null;
        }

        $lastJoinTime = $row['last_join_time'];
        if (is_string($lastJoinTime)) {
            $lastJoinTime = strtotime($lastJoinTime);
        }

        return new PlayerData(
            $row['username'],
            $row['last_os'],
            $lastJoinTime,
            (bool) $row['prefers_text'],
            $row['os_visibility'],
            (bool) $row['mute_friend_requests'],
        );
    }

    public function touchPlayerData(
        PlayerHandle $player,
        UserPreferences $defaultPreferences,
    ): Generator {
        yield from Await::promise(function ($resolve, $reject) use ($player, $defaultPreferences) {
            $this->db->executeGeneric('quickfriends.touch_player_data', [
                'uuid' => $player->uuid(),
                'username' => $player->username(),
                'last_os' => $player->lastOs(),
                'last_join_time' => $player->lastJoinTime(),
                'default_prefers_text' => $defaultPreferences->prefersText(),
                'default_os_visibility' => $defaultPreferences->osVisibility(),
                'default_mute_friend_requests' => $defaultPreferences->muteFriendRequests(),
            ], $resolve, $reject);
        });
    }

    public function updatePlayerData(
        string $player,
        PlayerData $playerData,
    ): Generator {
        yield from Await::promise(function ($resolve, $reject) use ($player, $playerData) {
            $this->db->executeGeneric('quickfriends.update_player_data', [
                'uuid' => $player,
                'username' => $playerData->username(),
                'last_os' => $playerData->lastOs(),
                'last_join_time' => $playerData->lastJoinTime(),
                'prefers_text' => $playerData->preferences()->prefersText(),
                'os_visibility' => $playerData->preferences()->osVisibility(),
                'mute_friend_requests' => $playerData->preferences()->muteFriendRequests(),
            ], $resolve, $reject);
        });
    }

    public function getFriendRequestStatus(
        PlayerHandle $requester,
        PlayerHandle $receiver,
        UserPreferences $defaultPreferences,
        int $maxFriends,
    ): Generator {
        $rows = yield from Await::promise(function ($resolve, $reject) use ($requester, $receiver, $defaultPreferences, $maxFriends) {
            $this->db->executeSelect('quickfriends.get_friend_request_status', [
                'requester_uuid' => $requester->uuid(),
                'requester_username' => $requester->username(),
                'requester_last_os' => $requester->lastOs(),
                'requester_last_join_time' => $requester->lastJoinTime(),
                'receiver_uuid' => $receiver->uuid(),
                'receiver_username' => $receiver->username(),
                'receiver_last_os' => $receiver->lastOs(),
                'receiver_last_join_time' => $receiver->lastJoinTime(),
                'default_prefers_text' => $defaultPreferences->prefersText(),
                'default_os_visibility' => $defaultPreferences->osVisibility(),
                'default_mute_friend_requests' => $defaultPreferences->muteFriendRequests(),
                'max_friends' => $maxFriends,
            ], $resolve, $reject);
        });

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        $code = $rows[0]['status'] ?? null;
        if (null === $code || !is_int($code) || !Codes::validRequest($code)) {
            throw new LogicException('The database procedure returned an invalid result code!');
        }

        return $code; // @phpstan-ignore-line
    }

    public function addFriendship(
        PlayerHandle $requester,
        PlayerHandle $accepter,
        int $creationTime,
        UserPreferences $defaultPreferences,
        int $maxFriends,
    ): Generator {
        $rows = yield from Await::promise(function ($resolve, $reject) use ($requester, $accepter, $creationTime, $defaultPreferences, $maxFriends) {
            $this->db->executeSelect('quickfriends.add_friend', [
                'requester_uuid' => $requester->uuid(),
                'requester_username' => $requester->username(),
                'requester_last_os' => $requester->lastOs(),
                'requester_last_join_time' => $requester->lastJoinTime(),
                'accepter_uuid' => $accepter->uuid(),
                'accepter_username' => $accepter->username(),
                'accepter_last_os' => $accepter->lastOs(),
                'accepter_last_join_time' => $accepter->lastJoinTime(),
                'creation_time' => $creationTime,
                'default_prefers_text' => $defaultPreferences->prefersText(),
                'default_os_visibility' => $defaultPreferences->osVisibility(),
                'default_mute_friend_requests' => $defaultPreferences->muteFriendRequests(),
                'max_friends' => $maxFriends,
            ], $resolve, $reject);
        });

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        $code = $rows[0]['status'] ?? null;
        if (null === $code || !is_int($code) || !Codes::validFriend($code)) {
            throw new LogicException('The database procedure returned an invalid result code!');
        }

        return $code; // @phpstan-ignore-line
    }

    public function removeFriendship(
        string $player1,
        string $player2,
    ): Generator {
        $rows = yield from Await::promise(function ($resolve, $reject) use ($player1, $player2) {
            $this->db->executeSelect('quickfriends.remove_friend', [
                'player1' => $player1,
                'player2' => $player2,
            ], $resolve, $reject);
        });

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        $code = $rows[0]['status'] ?? null;

        if (-1 === $code) {
            $requester = $rows[0]['requester'];
            $accepter = $rows[0]['accepter'];
            $creationTime = $rows[0]['creation_time'];
            if (is_string($creationTime)) {
                $creationTime = strtotime($creationTime);
            }
            if (null === $creationTime || !is_int($creationTime)) {
                throw new LogicException('creation_time should have been set because status was set to -1 (a.k.a unfriend successful), but it was set to '.print_r($creationTime, true));
            }

            return new Friendship($requester, $accepter, $creationTime);
        }

        if (null === $code || !is_int($code) || !Codes::validUnfriend($code)) {
            throw new LogicException('The database procedure returned an invalid result code!');
        }

        return $code; // @phpstan-ignore-line
    }

    public function listFriends(string $uuid): Generator
    {
        $rows = yield from Await::promise(function ($resolve, $reject) use ($uuid) {
            $this->db->executeSelect('quickfriends.list_friends', [
                'uuid' => $uuid,
            ], $resolve, $reject);
        });

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        $friendships = [];
        foreach ($rows as $row) {
            $creationTime = $row['creation_time'];
            if (is_string($creationTime)) {
                $creationTime = strtotime($creationTime);
            }
            $friendships[] = new Friendship(
                $row['requester'],
                $row['accepter'],
                $creationTime,
            );
        }

        return $friendships;
    }

    public function addBlockRelation(
        PlayerHandle $player,
        PlayerHandle $blocked,
        int $creationTime,
        UserPreferences $defaultPreferences,
    ): Generator {
        $rows = yield from Await::promise(function ($resolve, $reject) use ($player, $blocked, $creationTime, $defaultPreferences) {
            $this->db->executeSelect('quickfriends.add_block', [
                'player_uuid' => $player->uuid(),
                'player_username' => $player->username(),
                'player_last_os' => $player->lastOs(),
                'player_last_join_time' => $player->lastJoinTime(),
                'blocked_uuid' => $blocked->uuid(),
                'blocked_username' => $blocked->username(),
                'blocked_last_os' => $blocked->lastOs(),
                'blocked_last_join_time' => $blocked->lastJoinTime(),
                'creation_time' => $creationTime,
                'default_prefers_text' => $defaultPreferences->prefersText(),
                'default_os_visibility' => $defaultPreferences->osVisibility(),
                'default_mute_friend_requests' => $defaultPreferences->muteFriendRequests(),
            ], $resolve, $reject);
        });

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        $code = $rows[0]['status'] ?? null;

        if (null === $code || !is_int($code) || !Codes::validBlock($code)) {
            throw new LogicException('The database procedure returned an invalid result code!');
        }

        return $code; // @phpstan-ignore-line
    }

    public function removeBlockRelation(
        string $player,
        string $blocked,
    ): Generator {
        $rows = yield from Await::promise(function ($resolve, $reject) use ($player, $blocked) {
            $this->db->executeSelect('quickfriends.remove_block', [
                'player' => $player,
                'blocked' => $blocked,
            ], $resolve, $reject);
        });

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        $code = $rows[0]['status'] ?? null;

        if (-1 === $code) {
            $creationTime = $rows[0]['creation_time'];
            if (is_string($creationTime)) {
                $creationTime = strtotime($creationTime);
            }
            if (null === $creationTime || !is_int($creationTime)) {
                throw new LogicException('creation_time should have been set because status was set to -1 (a.k.a unblock successful), but it was set to '.print_r($creationTime, true));
            }

            return new BlockRelation($player, $blocked, $creationTime);
        }

        if (null === $code || !is_int($code) || !Codes::validUnblock($code)) {
            throw new LogicException('The database procedure returned an invalid result code!');
        }

        return $code; // @phpstan-ignore-line
    }

    public function getBlockRelations(
        string $player1,
        string $player2,
    ): Generator {
        $rows = yield from Await::promise(function ($resolve, $reject) use ($player1, $player2) {
            $this->db->executeSelect('quickfriends.get_blocks', [
                'uuids' => [$player1, $player2],
            ], $resolve, $reject);
        });

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        $blocks = [];
        foreach ($rows as $row) {
            $creationTime = $row['creation_time'];
            if (is_string($creationTime)) {
                $creationTime = strtotime($creationTime);
            }
            $blocks[] = new BlockRelation(
                $row['player'],
                $row['blocked'],
                $creationTime,
            );
        }

        return $blocks;
    }

    public function listBlocked(string $player): Generator
    {
        $rows = yield from Await::promise(function ($resolve, $reject) use ($player) {
            $this->db->executeSelect('quickfriends.list_blocked', [
                'player' => $player,
            ], $resolve, $reject);
        });

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        $blocks = [];
        foreach ($rows as $row) {
            $creationTime = $row['creation_time'];
            if (is_string($creationTime)) {
                $creationTime = strtotime($creationTime);
            }
            $blocks[] = new BlockRelation(
                $row['player'],
                $row['blocked'],
                $creationTime,
            );
        }

        return $blocks;
    }

    public function listBlockedBy(string $blocked): Generator
    {
        $rows = yield from Await::promise(function ($resolve, $reject) use ($blocked) {
            $this->db->executeSelect('quickfriends.list_blocked_by', [
                'blocked' => $blocked,
            ], $resolve, $reject);
        });

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        $blocks = [];
        foreach ($rows as $row) {
            $creationTime = $row['creation_time'];
            if (is_string($creationTime)) {
                $creationTime = strtotime($creationTime);
            }
            $blocks[] = new BlockRelation(
                $row['player'],
                $row['blocked'],
                $creationTime,
            );
        }

        return $blocks;
    }
}
