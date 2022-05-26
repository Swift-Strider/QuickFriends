<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Social;

use DiamondStrider1\QuickFriends\Database\Codes;
use DiamondStrider1\QuickFriends\Database\Database;
use DiamondStrider1\QuickFriends\Event\FriendAddedEvent;
use DiamondStrider1\QuickFriends\Event\FriendRemovedEvent;
use DiamondStrider1\QuickFriends\Event\FriendRequestEvent;
use DiamondStrider1\QuickFriends\Event\PlayerBlockedEvent;
use DiamondStrider1\QuickFriends\Event\PlayerUnblockedEvent;
use DiamondStrider1\QuickFriends\Structures\BlockRelation;
use DiamondStrider1\QuickFriends\Structures\FriendRequest;
use DiamondStrider1\QuickFriends\Structures\Friendship;
use DiamondStrider1\QuickFriends\Structures\PlayerHandle;
use DiamondStrider1\QuickFriends\Structures\UserPreferencesConfig;
use Generator;
use pocketmine\player\Player;

final class SocialPlayerApi
{
    public const FRIEND_RESULT_NOW_FRIENDS = 0;
    public const FRIEND_RESULT_NOTIFIED = 1;
    public const FRIEND_RESULT_MUTED = 2;
    public const FRIEND_RESULT_BLOCKED = 3;
    public const FRIEND_RESULT_BLOCKED_BY = 4;
    public const FRIEND_RESULT_ALREADY_FRIENDS = 5;
    public const FRIEND_RESULT_LIMIT_REACHED = 6;
    public const FRIEND_RESULT_OTHER_LIMIT_REACHED = 7;

    public const UNFRIEND_RESULT_NOW_REMOVED = 0;
    public const UNFRIEND_RESULT_NOT_FRIENDS = 1;

    public const BLOCK_RESULT_NOW_BLOCKED = 0;
    public const BLOCK_RESULT_ALSO_UNFRIENDED = 1;
    public const BLOCK_RESULT_ALREADY_BLOCKED = 1;

    public const UNBLOCK_RESULT_NOW_UNBLOCKED = 0;
    public const UNBLOCK_RESULT_NOT_BLOCKED = 1;

    public function __construct(
        private SocialRuntime $socialRuntime,
        private SocialConfig $socialConfig,
        private UserPreferencesConfig $userPreferencesConfig,
        private Database $database,
    ) {
    }

    public function getPlayerHandle(Player $player): PlayerHandle
    {
        return $this->socialRuntime->getPlayerHandle($player);
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, Friendship[]>
     */
    public function listFriends(string $player): Generator
    {
        return $this->database->listFriends($player);
    }

    /**
     * Tries to add $otherUuid as a friend and dispatches a
     * FriendRequestEvent if applicable.
     *
     * @phpstan-return Generator<mixed, mixed, mixed, self::FRIEND_RESULT_*>
     */
    public function addFriend(PlayerHandle $requester, PlayerHandle $receiver): Generator
    {
        $pending = $this->socialRuntime->getFriendRequest($receiver->uuid(), $requester->uuid());
        if (null !== $pending) {
            $code = yield from $this->database->addFriendship(
                $receiver, $requester, $friendTime = time(),
                $this->userPreferencesConfig->defaultPreferences(),
                $this->socialConfig->maxFriendLimit()
            );
            if (
                Codes::FRIEND_REQUESTER_LIMIT_REACHED !== $code &&
                Codes::FRIEND_ACCEPTER_LIMIT_REACHED !== $code
            ) {
                $this->socialRuntime->removeFriendRequest($receiver->uuid(), $requester->uuid());
            }
            if (Codes::FRIEND_NOW_FRIENDS === $code) {
                (new FriendAddedEvent(new Friendship(
                    $receiver, $requester, $friendTime,
                )))->call();
            }

            return match ($code) {
                Codes::FRIEND_NOW_FRIENDS => self::FRIEND_RESULT_NOW_FRIENDS,
                Codes::FRIEND_ALREADY_FRIENDS => self::FRIEND_RESULT_ALREADY_FRIENDS,
                Codes::FRIEND_REQUESTER_LIMIT_REACHED => self::FRIEND_RESULT_OTHER_LIMIT_REACHED,
                Codes::FRIEND_ACCEPTER_LIMIT_REACHED => self::FRIEND_RESULT_LIMIT_REACHED,
            };
        }

        $status = yield from $this->database->getFriendRequestStatus(
            $requester, $receiver,
            $this->userPreferencesConfig->defaultPreferences(), $this->socialConfig->maxFriendLimit()
        );
        $translation = match ($status) {
            Codes::REQUEST_NOTIFY => self::FRIEND_RESULT_NOTIFIED,
            Codes::REQUEST_MUTE => self::FRIEND_RESULT_MUTED,
            Codes::REQUEST_BLOCKED => self::FRIEND_RESULT_BLOCKED,
            Codes::REQUEST_BLOCKED_BY => self::FRIEND_RESULT_BLOCKED_BY,
            Codes::REQUEST_ALREADY_FRIENDS => self::FRIEND_RESULT_ALREADY_FRIENDS,
            Codes::REQUEST_LIMIT_REACHED => self::FRIEND_RESULT_LIMIT_REACHED,
        };
        $willNotify = match ($translation) {
            self::FRIEND_RESULT_NOTIFIED => true,
            self::FRIEND_RESULT_MUTED => false,
            default => null,
        };

        if (null === $willNotify) {
            return $translation;
        }

        $expireTime = $this->socialRuntime->addFriendRequest(
            $request = new FriendRequest(
                $requester, $receiver, microtime(true)
            )
        );

        (new FriendRequestEvent($request, $willNotify, $expireTime))->call();

        return $translation;
    }

    /**
     * Tries to remove $otherUuid as a friend and dispatches a
     * FriendRemovedEvent if applicable.
     *
     * @phpstan-return Generator<mixed, mixed, mixed, self::UNFRIEND_RESULT_*>
     */
    public function removeFriend(string $remover, string $other): Generator
    {
        $code = yield from $this->database->removeFriendship($remover, $other);
        if ($code instanceof Friendship) {
            (new FriendRemovedEvent(
                $code, $remover, $other
            ))->call();

            return self::UNFRIEND_RESULT_NOW_REMOVED;
        }

        return self::UNFRIEND_RESULT_NOT_FRIENDS;
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, self::BLOCK_RESULT_*>
     */
    public function blockPlayer(PlayerHandle $player, PlayerHandle $blocked): Generator
    {
        $code = yield from $this->database->addBlockRelation(
            $player, $blocked, $blockedTime = time(),
            $this->userPreferencesConfig->defaultPreferences(),
        );
        $alsoUnfriended = match ($code) {
            Codes::BLOCK_NOW_BLOCKED => false,
            Codes::BLOCK_ALSO_UNFRIENDED => true,
            Codes::BLOCK_ALREADY_BLOCKED => null,
        };
        if (null !== $alsoUnfriended) {
            (new PlayerBlockedEvent(
                new BlockRelation($player, $blocked, $blockedTime),
                $alsoUnfriended,
            ))->call();
        }

        return match ($code) {
            Codes::BLOCK_NOW_BLOCKED => self::BLOCK_RESULT_NOW_BLOCKED,
            Codes::BLOCK_ALSO_UNFRIENDED => self::BLOCK_RESULT_ALSO_UNFRIENDED,
            Codes::BLOCK_ALREADY_BLOCKED => self::BLOCK_RESULT_ALREADY_BLOCKED,
        };
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, self::UNBLOCK_RESULT_*>
     */
    public function unblockPlayer(string $player, string $blocked): Generator
    {
        $code = yield from $this->database->removeBlockRelation($player, $blocked);
        if ($code instanceof BlockRelation) {
            (new PlayerUnblockedEvent($code))->call();

            return self::UNBLOCK_RESULT_NOW_UNBLOCKED;
        }

        return self::UNBLOCK_RESULT_NOT_BLOCKED;
    }
}
