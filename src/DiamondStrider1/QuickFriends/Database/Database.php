<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Database;

use DiamondStrider1\QuickFriends\Structures\BlockRelation;
use DiamondStrider1\QuickFriends\Structures\Friendship;
use DiamondStrider1\QuickFriends\Structures\PlayerData;
use DiamondStrider1\QuickFriends\Structures\PlayerHandle;
use DiamondStrider1\QuickFriends\Structures\UserPreferences;
use Generator;

interface Database
{
    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function initialize(): Generator;

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, ?PlayerData>
     */
    public function getPlayerData(
        string $player
    ): Generator;

    /**
     * Writes data in $player to database, setting
     * preferences to $defaultPreferences if the player
     * wasn't previously in the database.
     *
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function touchPlayerData(
        PlayerHandle $player,
        UserPreferences $defaultPreferences,
    ): Generator;

    /**
     * Writes data in $playerData including preferences.
     * Always overwrites preference settings.
     *
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    public function updatePlayerData(
        string $player,
        PlayerData $playerData,
    ): Generator;

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, Codes::REQUEST_*>
     */
    public function getFriendRequestStatus(
        PlayerHandle $requester,
        PlayerHandle $receiver,
        UserPreferences $defaultPreferences,
        int $maxFriends,
    ): Generator;

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, Codes::FRIEND_*>
     */
    public function addFriendship(
        PlayerHandle $requester,
        PlayerHandle $accepter,
        int $creationTime,
        UserPreferences $defaultPreferences,
        int $maxFriends,
    ): Generator;

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, Friendship|Codes::UNFRIEND_*>
     */
    public function removeFriendship(
        string $player1,
        string $player2,
    ): Generator;

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, Friendship[]>
     */
    public function listFriends(string $uuid): Generator;

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, Codes::BLOCK_*>
     */
    public function addBlockRelation(
        PlayerHandle $player,
        PlayerHandle $blocked,
        int $creationTime,
        UserPreferences $defaultPreferences,
    ): Generator;

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, BlockRelation|Codes::UNBLOCK_*>
     */
    public function removeBlockRelation(
        string $player,
        string $blocked,
    ): Generator;

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, BlockRelation[]>
     */
    public function getBlockRelations(
        string $player1,
        string $player2,
    ): Generator;

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, BlockRelation[]>
     */
    public function listBlocked(string $player): Generator;

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, BlockRelation[]>
     */
    public function listBlockedBy(string $blocked): Generator;
}
