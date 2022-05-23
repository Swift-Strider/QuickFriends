<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Social;

use DiamondStrider1\QuickFriends\Event\BlockPlayerEvent;
use DiamondStrider1\QuickFriends\Event\FriendAddedEvent;
use DiamondStrider1\QuickFriends\Event\FriendRemovedEvent;
use DiamondStrider1\QuickFriends\Event\FriendRequestEvent;
use DiamondStrider1\QuickFriends\Event\UnblockPlayerEvent;
use DiamondStrider1\QuickFriends\Structures\BlockRelation;
use DiamondStrider1\QuickFriends\Structures\FriendRequest;
use DiamondStrider1\QuickFriends\Structures\Friendship;
use DiamondStrider1\QuickFriends\Structures\PlayerData;
use DiamondStrider1\QuickFriends\Structures\PlayerHandle;
use InvalidArgumentException;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;

final class SocialPlayerApi
{
    public const ADD_RESULT_NOW_FRIENDS = 0;
    public const ADD_RESULT_NOTIFIED = 1;
    public const ADD_RESULT_MUTED = 2;
    public const ADD_RESULT_BLOCKED = 3;
    public const ADD_RESULT_BLOCKED_BY = 4;
    public const ADD_RESULT_ALREADY_FRIENDS = 5;
    public const ADD_RESULT_LIMIT_REACHED = 6;

    public const REMOVE_RESULT_NOW_REMOVED = 0;
    public const REMOVE_RESULT_NOT_FRIENDS = 1;

    public const BLOCK_RESULT_NOW_BLOCKED = 0;
    public const BLOCK_RESULT_ALREADY_BLOCKED = 1;

    public const UNBLOCK_RESULT_NOW_UNBLOCKED = 0;
    public const UNBLOCK_RESULT_NOT_BLOCKED = 1;

    public function __construct(
        private SocialDao $socialDao,
        private SocialRuntime $socialRuntime,
        private SocialConfig $socialConfig,
    ) {
    }

    /**
     * @phpstan-return Promise<Friendship[]>
     */
    public function listFriends(string $player): Promise
    {
        return $this->socialDao->listFriends($player);
    }

    /**
     * Tries to add $otherUuid as a friend and dispatches a
     * FriendRequestEvent if applicable.
     *
     * @phpstan-return Promise<self::ADD_RESULT_*>
     */
    public function addFriend(PlayerHandle $requester, PlayerHandle $receiver): Promise
    {
        $resolver = new PromiseResolver();

        if ($requester->uuid() === $receiver->uuid()) {
            throw new InvalidArgumentException('The uuids must be different!');
        }

        $this->listFriends($requester->uuid())->onCompletion(function ($friendships) use ($requester, $receiver, $resolver) {
            $reqUuid = $requester->uuid();
            $recUuid = $receiver->uuid();
            foreach ($friendships as $f) {
                if (
                    ($f->requester() === $reqUuid && $f->accepter() === $recUuid) ||
                    ($f->requester() === $recUuid && $f->accepter() === $reqUuid)
                ) {
                    $resolver->resolve(self::ADD_RESULT_ALREADY_FRIENDS);

                    return;
                }
            }

            if (count($friendships) >= $this->socialConfig->maxFriendLimit()) {
                $resolver->resolve(self::ADD_RESULT_LIMIT_REACHED);

                return;
            }
            $blockedBy = $this->socialDao->listBlockedBy($requester->uuid());
            $this->socialDao->listBlocked($requester->uuid())->onCompletion(function ($blockList) use ($requester, $receiver, $resolver, $blockedBy) {
                foreach ($blockList as $block) {
                    if ($block->blocked() === $receiver->uuid()) {
                        $resolver->resolve(self::ADD_RESULT_BLOCKED);

                        return;
                    }
                }
                $blockedBy->onCompletion(function ($blockedByList) use ($requester, $receiver, $resolver) {
                    foreach ($blockedByList as $block) {
                        if ($block->player() === $receiver->uuid()) {
                            $resolver->resolve(self::ADD_RESULT_BLOCKED_BY);

                            return;
                        }
                    }
                    $existingRequest = $this->socialRuntime->getFriendRequest($receiver->uuid(), $requester->uuid());
                    if (null !== $existingRequest) {
                        $existingRequest->claimed = true;
                        $this->socialDao->addFriend($receiver, $requester, $creationTime = time())->onCompletion(function () use ($resolver, $requester, $receiver, $creationTime) {
                            $resolver->resolve(self::ADD_RESULT_NOW_FRIENDS);
                            (new FriendAddedEvent(new Friendship(
                                $receiver->uuid(), $requester->uuid(), $creationTime
                            )))->call();
                        }, function () {});
                    } else {
                        $this->socialRuntime->addFriendRequest($newRequest = new FriendRequest(
                            $requester->uuid(), $receiver->uuid(), time(), false
                        ));
                        $this->socialDao->getPlayerDataOrTouch($receiver)->onCompletion(function (PlayerData $playerData) use ($resolver, $newRequest) {
                            $muted = $playerData->preferences()->muteFriendRequests();
                            $resolver->resolve(
                                match ($muted) {
                                    true => self::ADD_RESULT_MUTED,
                                    false => self::ADD_RESULT_NOTIFIED
                                }
                            );
                            (new FriendRequestEvent($newRequest, $muted))->call();
                        }, function () {});
                    }
                }, function () {});
            }, function () {});
        }, function () {});

        return $resolver->getPromise();
    }

    /**
     * Tries to remove $otherUuid as a friend and dispatches a
     * FriendRemovedEvent if applicable.
     *
     * @phpstan-return Promise<self::REMOVE_RESULT_*>
     */
    public function removeFriend(string $uuid, string $otherUuid): Promise
    {
        $resolver = new PromiseResolver();

        if ($uuid === $otherUuid) {
            throw new InvalidArgumentException('The uuids must be different!');
        }

        $this->listFriends($uuid)->onCompletion(function ($friendships) use ($uuid, $otherUuid, $resolver) {
            $previousFriendship = null;
            foreach ($friendships as $f) {
                if (
                    ($f->requester() === $uuid && $f->accepter() === $otherUuid) ||
                    ($f->requester() === $otherUuid && $f->accepter() === $uuid)
                ) {
                    $previousFriendship = $f;
                }
            }
            if (null === $previousFriendship) {
                $resolver->resolve(self::REMOVE_RESULT_NOT_FRIENDS);

                return;
            }
            $this->socialDao->removeFriend($uuid, $otherUuid)->onCompletion(
                function () use ($uuid, $otherUuid, $resolver, $previousFriendship) {
                    $resolver->resolve(self::REMOVE_RESULT_NOW_REMOVED);

                    (new FriendRemovedEvent(
                        $previousFriendship, $uuid, $otherUuid
                    ))->call();
                },
                function () {}
            );
        }, function () {});

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<self::BLOCK_RESULT_*>
     */
    public function blockPlayer(PlayerHandle $player, PlayerHandle $blocked): Promise
    {
        $resolver = new PromiseResolver();

        if ($player === $blocked) {
            throw new InvalidArgumentException('The uuids must be different!');
        }

        $this->removeFriend($player->uuid(), $blocked->uuid())->onCompletion(
            function () use ($resolver, $player, $blocked) {
                $this->socialDao->getBlockRelation($player->uuid(), $blocked->uuid())->onCompletion(
                    function (?BlockRelation $blockRelation) use ($player, $blocked, $resolver) {
                        if (null !== $blockRelation) {
                            $resolver->resolve(self::BLOCK_RESULT_ALREADY_BLOCKED);

                            return;
                        }
                        $this->socialDao->blockPlayer($player, $blocked, $creationTime = time())->onCompletion(
                            function () use ($player, $blocked, $resolver, $creationTime) {
                                $resolver->resolve(self::BLOCK_RESULT_NOW_BLOCKED);
                                (new BlockPlayerEvent(new BlockRelation(
                                    $player->uuid(), $blocked->uuid(), $creationTime
                                )))->call();
                            }, function () {}
                        );
                    }, function () {}
                );
            }, function () {}
        );

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<self::UNBLOCK_RESULT_*>
     */
    public function unblockPlayer(string $uuid, string $otherUuid): Promise
    {
        $resolver = new PromiseResolver();

        if ($uuid === $otherUuid) {
            throw new InvalidArgumentException('The uuids must be different!');
        }

        $this->socialDao->getBlockRelation($uuid, $otherUuid)->onCompletion(
            function (?BlockRelation $blockRelation) use ($uuid, $otherUuid, $resolver) {
                if (null === $blockRelation) {
                    $resolver->resolve(self::UNBLOCK_RESULT_NOT_BLOCKED);

                    return;
                }
                $this->socialDao->unblockPlayer($uuid, $otherUuid)->onCompletion(
                    function () use ($blockRelation, $resolver) {
                        $resolver->resolve(self::UNBLOCK_RESULT_NOW_UNBLOCKED);
                        (new UnblockPlayerEvent($blockRelation))->call();
                    }, function () {}
                );
            }, function () {}
        );

        return $resolver->getPromise();
    }
}
