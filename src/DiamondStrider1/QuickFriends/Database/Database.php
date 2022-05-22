<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Database;

use Closure;
use DiamondStrider1\QuickFriends\Structures\Friendship;
use DiamondStrider1\QuickFriends\Structures\PlayerData;
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
                ));
            }
        }, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(): void $callback
     */
    public function setPlayerData(
        string $uuid,
        string $username,
        string $lastOs,
        Closure $callback
    ): void {
        $this->db->executeGeneric('quickfriends.set_player_data', [
            'uuid' => $uuid,
            'username' => $username,
            'last_os' => $lastOs,
        ], $callback, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(?UserPreferences): void $callback
     */
    public function getUserPreferences(string $uuid, Closure $callback): void
    {
        $this->db->executeSelect('quickfriends.get_user_preferences', [
            'uuid' => $uuid,
        ], function ($rows) use ($callback) {
            $row = $rows[0] ?? null;
            if (null === $row) {
                ($callback)(null);
            } else {
                ($callback)(new UserPreferences(
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
    public function setUserPreferences(
        string $uuid,
        UserPreferences $userPreferences,
        Closure $callback
    ): void {
        $this->db->executeGeneric('quickfriends.set_user_preferences', [
            'uuid' => $uuid,
            'prefers_text' => (int) $userPreferences->prefersText(),
            'os_visibility' => $userPreferences->osVisibility(),
            'mute_friend_requests' => (int) $userPreferences->muteFriendRequests(),
        ], $callback, function ($error) {
            throw $error;
        });
    }

    /**
     * @phpstan-param Closure(): void $callback
     */
    public function addFriend(
        string $requester,
        string $accepter,
        Closure $callback
    ): void {
        $this->db->executeGeneric('quickfriends.add_friend', [
            'requester' => $requester,
            'accepter' => $accepter,
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
}
