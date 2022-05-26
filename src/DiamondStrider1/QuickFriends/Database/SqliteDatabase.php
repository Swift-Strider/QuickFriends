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
use SOFe\AwaitGenerator\Mutex;

final class SqliteDatabase implements Database
{
    private Mutex $lock;

    public function __construct(
        private DataConnector $db
    ) {
        $this->lock = new Mutex();
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

        $row['uuid'] = $player;

        return StructureParser::parsePlayerData($row);
    }

    public function touchPlayerData(
        PlayerHandle $player,
        UserPreferences $defaultPreferences,
    ): Generator {
        // May be done with other reading operations.
        yield from Await::promise(
            function ($resolve, $reject) use ($player, $defaultPreferences) {
                $this->db->executeGeneric('quickfriends.touch_player_data', [
                    'uuid' => $player->uuid(),
                    'username' => $player->username(),
                    'last_os' => $player->lastOs(),
                    'last_join_time' => $player->lastJoinTime(),
                    'default_prefers_text' => $defaultPreferences->prefersText(),
                    'default_os_visibility' => $defaultPreferences->osVisibility(),
                    'default_mute_friend_requests' => $defaultPreferences->muteFriendRequests(),
                ], $resolve, $reject);
            }
        );
    }

    public function updatePlayerData(
        string $player,
        PlayerData $playerData,
    ): Generator {
        // May be done with other reading operations.
        yield from Await::promise(
            function ($resolve, $reject) use ($player, $playerData) {
                $this->db->executeGeneric('quickfriends.update_player_data', [
                    'uuid' => $player,
                    'username' => $playerData->username(),
                    'last_os' => $playerData->lastOs(),
                    'last_join_time' => $playerData->lastJoinTime(),
                    'prefers_text' => $playerData->preferences()->prefersText(),
                    'os_visibility' => $playerData->preferences()->osVisibility(),
                    'mute_friend_requests' => $playerData->preferences()->muteFriendRequests(),
                ], $resolve, $reject);
            }
        );
    }

    public function getFriendRequestStatus(
        PlayerHandle $requester,
        PlayerHandle $receiver,
        UserPreferences $defaultPreferences,
        int $maxFriends,
    ): Generator {
        yield from $this->lock->acquire();
        $blocks = yield from $this->getBlockRelations($requester->uuid(), $receiver->uuid());
        $isBlockedBy = false;
        foreach ($blocks as $block) {
            if ($block->player()->uuid() === $requester->uuid()) {
                $this->lock->release();

                return Codes::REQUEST_BLOCKED;
            } else {
                $isBlockedBy = true;
            }
        }
        if ($isBlockedBy) {
            $this->lock->release();

            return Codes::REQUEST_BLOCKED_BY;
        }

        $rows = yield from Await::promise(
            function ($resolve, $reject) use ($requester, $receiver) {
                $this->db->executeSelect('quickfriends.get_friendship', [
                    'uuids' => [$requester->uuid(), $receiver->uuid()],
                ], $resolve, $reject);
            }
        );

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        $row = $rows[0] ?? null;
        if (null !== $row) {
            $this->lock->release();

            return Codes::REQUEST_ALREADY_FRIENDS;
        }

        $friends = yield from $this->listFriends($requester->uuid());
        if (count($friends) >= $maxFriends) {
            $this->lock->release();

            return Codes::REQUEST_LIMIT_REACHED;
        }

        yield from $this->touchPlayerData($receiver, $defaultPreferences);
        $receiverData = yield from $this->getPlayerData($receiver->uuid());
        $this->lock->release();

        if (!$receiverData instanceof PlayerData) {
            throw new LogicException('$receiverData is guaranteed to not be null!');
        }

        if ($receiverData->preferences()->muteFriendRequests()) {
            return Codes::REQUEST_MUTE;
        }

        return Codes::REQUEST_NOTIFY;
    }

    public function addFriendship(
        PlayerHandle $requester,
        PlayerHandle $accepter,
        int $creationTime,
        UserPreferences $defaultPreferences,
        int $maxFriends,
    ): Generator {
        yield from $this->lock->acquire();
        yield from $this->_removeBlockRelation($requester->uuid(), $accepter->uuid());
        yield from $this->_removeBlockRelation($accepter->uuid(), $requester->uuid());
        $friendships = yield from $this->listFriends($requester->uuid());
        $rUuid = $requester->uuid();
        $aUuid = $accepter->uuid();
        foreach ($friendships as $f) {
            if (
                $f->requester()->uuid() === $rUuid && $f->accepter()->uuid() === $aUuid ||
                $f->requester()->uuid() === $aUuid && $f->accepter()->uuid() === $rUuid
            ) {
                $this->lock->release();

                return Codes::FRIEND_ALREADY_FRIENDS;
            }
        }

        if (count($friendships) >= $maxFriends) {
            $this->lock->release();

            return Codes::FRIEND_LIMIT_REACHED;
        }

        yield from Await::promise(function ($resolve, $reject) use ($requester, $accepter, $creationTime, $defaultPreferences) {
            $this->db->executeGeneric('quickfriends.add_friend', [
                'requester_uuid' => $requester->uuid(),
                'requester_username' => $requester->username(),
                'requester_last_os' => $requester->lastOs(),
                'requester_last_join_time' => $requester->lastJoinTime(),
                'accepter_uuid' => $accepter->uuid(),
                'accepter_username' => $accepter->username(),
                'accepter_last_os' => $accepter->lastOs(),
                'accepter_last_join_time' => $accepter->lastJoinTime(),
                'default_prefers_text' => $defaultPreferences->prefersText(),
                'default_os_visibility' => $defaultPreferences->osVisibility(),
                'default_mute_friend_requests' => $defaultPreferences->muteFriendRequests(),
                'creation_time' => $creationTime,
            ], $resolve, $reject);
        });
        $this->lock->release();

        return Codes::FRIEND_NOW_FRIENDS;
    }

    public function removeFriendship(
        string $player1,
        string $player2,
    ): Generator {
        yield from $this->lock->acquire();
        $code = yield from $this->_removeFriendship($player1, $player2);
        $this->lock->release();

        return $code;
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, Friendship|Codes::UNFRIEND_*>
     */
    private function _removeFriendship(
        string $player1,
        string $player2,
    ): Generator {
        $rows = yield from Await::promise(function ($resolve, $reject) use ($player1, $player2) {
            $this->db->executeSelect('quickfriends.get_friendship', [
                'uuids' => [$player1, $player2],
            ], $resolve, $reject);
        });

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        yield from Await::promise(function ($resolve, $reject) use ($player1, $player2) {
            $this->db->executeGeneric('quickfriends.remove_friend', [
                'uuids' => [$player1, $player2],
            ], $resolve, $reject);
        });

        $row = $rows[0] ?? null;
        if (null !== $row) {
            return StructureParser::parseFriendship($row);
        }

        return Codes::UNFRIEND_NOT_FRIENDS;
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
            $friendships[] = StructureParser::parseFriendship($row);
        }

        return $friendships;
    }

    public function addBlockRelation(
        PlayerHandle $player,
        PlayerHandle $blocked,
        int $creationTime,
        UserPreferences $defaultPreferences,
    ): Generator {
        yield from $this->lock->acquire();
        $blocks = yield from $this->getBlockRelations($player->uuid(), $blocked->uuid());
        $pUuid = $player->uuid();
        $bUuid = $blocked->uuid();
        foreach ($blocks as $block) {
            if ($block->player()->uuid() === $pUuid &&
                $block->blocked()->uuid() === $bUuid
            ) {
                $this->lock->release();

                return Codes::BLOCK_ALREADY_BLOCKED;
            }
        }
        $removeCode = yield from $this->_removeFriendship($player->uuid(), $blocked->uuid());
        yield from Await::promise(function ($resolve, $reject) use ($player, $blocked, $creationTime, $defaultPreferences) {
            $this->db->executeGeneric('quickfriends.add_block', [
                'player_uuid' => $player->uuid(),
                'player_username' => $player->username(),
                'player_last_os' => $player->lastOs(),
                'player_last_join_time' => $player->lastJoinTime(),
                'blocked_uuid' => $blocked->uuid(),
                'blocked_username' => $blocked->username(),
                'blocked_last_os' => $blocked->lastOs(),
                'blocked_last_join_time' => $blocked->lastJoinTime(),
                'default_prefers_text' => $defaultPreferences->prefersText(),
                'default_os_visibility' => $defaultPreferences->osVisibility(),
                'default_mute_friend_requests' => $defaultPreferences->muteFriendRequests(),
                'creation_time' => $creationTime,
            ], $resolve, $reject);
        });
        $this->lock->release();
        if ($removeCode instanceof Friendship) {
            return Codes::BLOCK_ALSO_UNFRIENDED;
        }

        return Codes::BLOCK_NOW_BLOCKED;
    }

    public function removeBlockRelation(
        string $player,
        string $blocked,
    ): Generator {
        yield from $this->lock->acquire();
        $rows = yield from Await::promise(
            function ($resolve, $reject) use ($player, $blocked) {
                $this->db->executeSelect('quickfriends.get_blocks', [
                    'uuids' => [$player, $blocked],
                ], $resolve, $reject);
            }
        );

        if (!is_array($rows)) {
            throw new LogicException('The variable $rows is not an array!');
        }

        yield from $this->_removeBlockRelation($player, $blocked);
        $this->lock->release();

        $row = $rows[0] ?? null;
        if (null !== $row) {
            return StructureParser::parseBlockRelation($row);
        }

        return Codes::UNFRIEND_NOT_FRIENDS;
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, void>
     */
    private function _removeBlockRelation(
        string $player,
        string $blocked,
    ): Generator {
        yield from Await::promise(
            function ($resolve, $reject) use ($player, $blocked) {
                $this->db->executeChange('quickfriends.remove_block', [
                    'player' => $player,
                    'blocked' => $blocked,
                ], $resolve, $reject);
            }
        );
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
            $blocks[] = StructureParser::parseBlockRelation($row);
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
