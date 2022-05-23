<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Database;

use Closure;
use DiamondStrider1\QuickFriends\Structures\BlockRelation;
use DiamondStrider1\QuickFriends\Structures\Friendship;
use DiamondStrider1\QuickFriends\Structures\PlayerData;
use DiamondStrider1\QuickFriends\Structures\PlayerHandle;
use DiamondStrider1\QuickFriends\Structures\UserPreferences;
use poggit\libasynql\DataConnector;

final class Database
{
    public function __construct(
        private DataConnector $db
    ) {
    }

    /**
     * @phpstan-param Closure(): void $callback
     */
    public function initialize(Closure $callback): void
    {
        $this->db->executeGeneric('quickfriends.init', [], function () use ($callback) {
            ($callback)();
        }, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(?PlayerData): void $callback
     */
    public function getPlayerData(string $uuid, Closure $callback): void
    {
        $this->db->executeSelect('quickfriends.get_player_data', [
            'uuid' => $uuid,
        ], function ($rows) use ($callback) {
            $row = $rows[0] ?? null;
            if (null === $row) {
                ($callback)(null);
            } else {
                $lastJoinTime = $row['last_join_time'];
                if (is_string($lastJoinTime)) {
                    $lastJoinTime = strtotime($lastJoinTime);
                }
                ($callback)(new PlayerData(
                    $row['username'],
                    $row['last_os'],
                    $lastJoinTime,
                    (bool) $row['prefers_text'],
                    $row['os_visibility'],
                    (bool) $row['mute_friend_requests'],
                ));
            }
        }, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(): void $callback
     */
    public function touchPlayerData(
        PlayerHandle $player,
        UserPreferences $defaultPreferences,
        Closure $callback
    ): void {
        $this->db->executeGeneric('quickfriends.touch_player_data', [
            'uuid' => $player->uuid(),
            'username' => $player->username(),
            'last_os' => $player->lastOs(),
            'last_join_time' => $player->lastJoinTime(),
            'default_prefers_text' => (int) $defaultPreferences->prefersText(),
            'default_os_visibility' => $defaultPreferences->osVisibility(),
            'default_mute_friend_requests' => (int) $defaultPreferences->muteFriendRequests(),
        ], $callback, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(): void $callback
     */
    public function updatePlayerData(
        string $uuid,
        PlayerData $playerData,
        Closure $callback
    ): void {
        $this->db->executeGeneric('quickfriends.update_player_data', [
            'uuid' => $uuid,
            'username' => $playerData->username(),
            'last_os' => $playerData->lastOs(),
            'last_join_time' => $playerData->lastJoinTime(),
            'prefers_text' => (int) $playerData->preferences()->prefersText(),
            'os_visibility' => $playerData->preferences()->osVisibility(),
            'mute_friend_requests' => (int) $playerData->preferences()->muteFriendRequests(),
        ], $callback, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(): void $callback
     */
    public function addFriend(
        PlayerHandle $requester,
        PlayerHandle $accepter,
        int $creationTime,
        UserPreferences $defaultPreferences,
        Closure $callback
    ): void {
        $this->db->executeGeneric('quickfriends.add_friend', [
            'requester_uuid' => $requester->uuid(),
            'requester_username' => $requester->username(),
            'requester_last_os' => $requester->lastOs(),
            'requester_last_join_time' => $requester->lastJoinTime(),
            'accepter_uuid' => $accepter->uuid(),
            'accepter_username' => $accepter->username(),
            'accepter_last_os' => $accepter->lastOs(),
            'accepter_last_join_time' => $accepter->lastJoinTime(),
            'creation_time' => $creationTime,
            'default_prefers_text' => (int) $defaultPreferences->prefersText(),
            'default_os_visibility' => $defaultPreferences->osVisibility(),
            'default_mute_friend_requests' => (int) $defaultPreferences->muteFriendRequests(),
        ], $callback, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(): void $callback
     */
    public function removeFriend(
        string $uuid1,
        string $uuid2,
        Closure $callback
    ): void {
        $this->db->executeGeneric('quickfriends.remove_friend', [
            'uuids' => [$uuid1, $uuid2],
        ], $callback, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(Friendship[]): void $callback
     */
    public function getFriends(
        string $uuid,
        Closure $callback
    ): void {
        $this->db->executeSelect('quickfriends.list_friends', [
            'uuid' => $uuid,
        ], function ($rows) use ($callback) {
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
            ($callback)($friendships);
        }, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(): void $callback
     */
    public function blockPlayer(
        PlayerHandle $player,
        PlayerHandle $blocked,
        int $creationTime,
        UserPreferences $defaultPreferences,
        Closure $callback
    ): void {
        $this->db->executeGeneric('quickfriends.block_player', [
            'player_uuid' => $player->uuid(),
            'player_username' => $player->username(),
            'player_last_os' => $player->lastOs(),
            'player_last_join_time' => $player->lastJoinTime(),
            'blocked_uuid' => $blocked->uuid(),
            'blocked_username' => $blocked->username(),
            'blocked_last_os' => $blocked->lastOs(),
            'blocked_last_join_time' => $blocked->lastJoinTime(),
            'creation_time' => $creationTime,
            'default_prefers_text' => (int) $defaultPreferences->prefersText(),
            'default_os_visibility' => $defaultPreferences->osVisibility(),
            'default_mute_friend_requests' => (int) $defaultPreferences->muteFriendRequests(),
        ], $callback, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(): void $callback
     */
    public function unblockPlayer(
        string $player,
        string $blocked,
        Closure $callback
    ): void {
        $this->db->executeGeneric('quickfriends.unblock_player', [
            'player' => $player,
            'blocked' => $blocked,
        ], $callback, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(BlockRelation[]): void $callback
     */
    public function listBlocked(
        string $player,
        Closure $callback
    ): void {
        $this->db->executeSelect('quickfriends.list_blocked', [
            'uuid' => $player,
        ], function ($rows) use ($callback) {
            $blockRelations = [];
            foreach ($rows as $row) {
                $creationTime = $row['creation_time'];
                if (is_string($creationTime)) {
                    $creationTime = strtotime($creationTime);
                }
                $blockRelations[] = new BlockRelation(
                    $row['player'],
                    $row['blocked'],
                    $creationTime,
                );
            }
            ($callback)($blockRelations);
        }, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(BlockRelation[]): void $callback
     */
    public function listBlockedBy(
        string $blocked,
        Closure $callback
    ): void {
        $this->db->executeSelect('quickfriends.list_blocked_by', [
            'uuid' => $blocked,
        ], function ($rows) use ($callback) {
            $blockRelations = [];
            foreach ($rows as $row) {
                $creationTime = $row['creation_time'];
                if (is_string($creationTime)) {
                    $creationTime = strtotime($creationTime);
                }
                $blockRelations[] = new BlockRelation(
                    $row['player'],
                    $row['blocked'],
                    $creationTime,
                );
            }
            ($callback)($blockRelations);
        }, function ($error) {
            throw $error;
        });
    }
}
