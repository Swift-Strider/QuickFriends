<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Social;

use DiamondStrider1\QuickFriends\Database\Database;
use DiamondStrider1\QuickFriends\Structures\BlockRelation;
use DiamondStrider1\QuickFriends\Structures\Friendship;
use DiamondStrider1\QuickFriends\Structures\PlayerData;
use DiamondStrider1\QuickFriends\Structures\PlayerHandle;
use DiamondStrider1\QuickFriends\Structures\UserPreferencesConfig;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;

/**
 * Data Access Object for the Social Module.
 */
final class SocialDao
{
    /**
     * @phpstan-var array<string, Friendship[]>
     */
    private array $friendCache = [];

    /**
     * @phpstan-var array<string, BlockRelation[]>
     */
    private array $blockCache = [];

    /**
     * @phpstan-var array<string, BlockRelation[]>
     */
    private array $blockedByCache = [];

    /**
     * @phpstan-param Promise<Database> $database
     */
    public function __construct(
        private Promise $database,
        private UserPreferencesConfig $userPreferencesConfig,
    ) {
    }

    /**
     * @phpstan-return Promise<null>
     */
    public function touchPlayerData(PlayerHandle $player): Promise
    {
        $resolver = new PromiseResolver();

        $this->database->onCompletion(function (Database $database) use ($player, $resolver) {
            $database->touchPlayerData(
                $player, $this->userPreferencesConfig->defaultPreferences(),
                function () use ($resolver) {
                    $resolver->resolve(null);
                }
            );
        }, function () {});

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<?PlayerData>
     */
    public function getPlayerData(string $player): Promise
    {
        $resolver = new PromiseResolver();

        $this->database->onCompletion(function (Database $database) use ($player, $resolver) {
            $database->getPlayerData($player, function (?PlayerData $playerData) use ($resolver) {
                $resolver->resolve($playerData);
            });
        }, function () {});

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<PlayerData>
     */
    public function getPlayerDataOrTouch(PlayerHandle $player): Promise
    {
        $resolver = new PromiseResolver();

        $this->database->onCompletion(function (Database $database) use ($player, $resolver) {
            $database->getPlayerData($player->uuid(), function (?PlayerData $playerData) use ($player, $resolver, $database) {
                if (null !== $playerData) {
                    $resolver->resolve($playerData);

                    return;
                }
                $prefs = $this->userPreferencesConfig->defaultPreferences();
                $database->touchPlayerData(
                    $player, $prefs,
                    function () use ($player, $resolver, $prefs) {
                        $resolver->resolve(new PlayerData(
                            $player->username(),
                            $player->lastOs(),
                            $player->lastJoinTime(),
                            $prefs->prefersText(),
                            $prefs->osVisibility(),
                            $prefs->muteFriendRequests(),
                        ));
                    }
                );
            });
        }, function () {});

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<?Friendship>
     */
    public function getFriendship(string $uuid, string $otherUuid): Promise
    {
        $resolver = new PromiseResolver();

        $this->listFriends($uuid)->onCompletion(
            function ($friendships) use ($otherUuid, $resolver) {
                foreach ($friendships as $f) {
                    if ($f->requester() === $otherUuid || $f->accepter() === $otherUuid) {
                        $resolver->resolve($f);

                        return;
                    }
                }
                $resolver->resolve(null);
            }, function () {}
        );

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<Friendship[]>
     */
    public function listFriends(string $uuid, bool $forceRefresh = false): Promise
    {
        $resolver = new PromiseResolver();
        if (isset($this->friendCache[$uuid]) && !$forceRefresh) {
            $resolver->resolve($this->friendCache[$uuid]);
        } else {
            $this->database->onCompletion(
                function (Database $database) use ($uuid, $resolver) {
                    $database->getFriends($uuid, function (array $friends) use ($uuid, $resolver) {
                        $this->friendCache[$uuid] = $friends;
                        $resolver->resolve($friends);
                    });
                },
                function () {}
            );
        }

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<null>
     */
    public function addFriend(PlayerHandle $requester, PlayerHandle $accepter, int $creationTime): Promise
    {
        $resolver = new PromiseResolver();
        $this->database->onCompletion(
            function (Database $database) use ($requester, $accepter, $creationTime, $resolver) {
                $database->addFriend(
                    $requester,
                    $accepter,
                    $creationTime,
                    $this->userPreferencesConfig->defaultPreferences(),
                    function () use ($requester, $accepter, $resolver) {
                        // Update Friend Caches
                        $requester = $this->listFriends($requester->uuid(), true);
                        $accepter = $this->listFriends($accepter->uuid(), true);

                        $waitGroup = 2;
                        $onComplete = function () use (&$waitGroup, $resolver) {
                            --$waitGroup;
                            if (0 === $waitGroup) {
                                $resolver->resolve(null);
                            }
                        };

                        $requester->onCompletion($onComplete, function () {
                        });
                        $accepter->onCompletion($onComplete, function () {
                        });
                    }
                );
            },
            function () {}
        );

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<null>
     */
    public function removeFriend(string $uuid1, string $uuid2): Promise
    {
        $resolver = new PromiseResolver();
        $this->database->onCompletion(
            function (Database $database) use ($uuid1, $uuid2, $resolver) {
                $database->removeFriend(
                    $uuid1,
                    $uuid2,
                    function () use ($uuid1, $uuid2, $resolver) {
                        // Update Friend Caches
                        $player1 = $this->listFriends($uuid1, true);
                        $player2 = $this->listFriends($uuid2, true);

                        $waitGroup = 2;
                        $onComplete = function () use (&$waitGroup, $resolver) {
                            --$waitGroup;
                            if (0 === $waitGroup) {
                                $resolver->resolve(null);
                            }
                        };

                        $player1->onCompletion($onComplete, function () {
                        });
                        $player2->onCompletion($onComplete, function () {
                        });
                    }
                );
            },
            function () {}
        );

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<?BlockRelation>
     */
    public function getBlockRelation(string $player, string $blocked): Promise
    {
        $resolver = new PromiseResolver();

        $this->listBlocked($player)->onCompletion(
            function ($blockRelations) use ($blocked, $resolver) {
                foreach ($blockRelations as $b) {
                    if ($b->blocked() === $blocked) {
                        $resolver->resolve($b);

                        return;
                    }
                }
                $resolver->resolve(null);
            }, function () {}
        );

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<BlockRelation[]>
     */
    public function listBlocked(string $uuid, bool $forceRefresh = false): Promise
    {
        $resolver = new PromiseResolver();

        if (isset($this->blockCache[$uuid]) && !$forceRefresh) {
            $resolver->resolve($this->blockCache[$uuid]);
        } else {
            $this->database->onCompletion(
                function (Database $database) use ($uuid, $resolver) {
                    $database->listBlocked(
                        $uuid,
                        function ($blockList) use ($uuid, $resolver) {
                            $this->blockCache[$uuid] = $blockList;
                            $resolver->resolve($blockList);
                        }
                    );
                },
                function () {}
            );
        }

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<BlockRelation[]>
     */
    public function listBlockedBy(string $uuid, bool $forceRefresh = false): Promise
    {
        $resolver = new PromiseResolver();

        if (isset($this->blockedByCache[$uuid]) && !$forceRefresh) {
            $resolver->resolve($this->blockedByCache[$uuid]);
        } else {
            $this->database->onCompletion(
                function (Database $database) use ($uuid, $resolver) {
                    $database->listBlockedBy(
                        $uuid,
                        function ($blockList) use ($uuid, $resolver) {
                            $this->blockedByCache[$uuid] = $blockList;
                            $resolver->resolve($blockList);
                        }
                    );
                },
                function () {}
            );
        }

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<null>
     */
    public function blockPlayer(PlayerHandle $player, PlayerHandle $blocked, int $creationTime): Promise
    {
        $resolver = new PromiseResolver();
        $this->database->onCompletion(
            function (Database $database) use ($player, $blocked, $creationTime, $resolver) {
                $database->blockPlayer(
                    $player,
                    $blocked,
                    $creationTime,
                    $this->userPreferencesConfig->defaultPreferences(),
                    function () use ($player, $blocked, $resolver) {
                        // Update Block List Caches
                        $player = $this->listBlocked($player->uuid(), true);
                        $blocked = $this->listBlockedBy($blocked->uuid(), true);

                        $waitGroup = 2;
                        $onComplete = function () use (&$waitGroup, $resolver) {
                            --$waitGroup;
                            if (0 === $waitGroup) {
                                $resolver->resolve(null);
                            }
                        };

                        $player->onCompletion($onComplete, function () {
                        });
                        $blocked->onCompletion($onComplete, function () {
                        });
                    }
                );
            },
            function () {}
        );

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Promise<null>
     */
    public function unblockPlayer(string $player, string $blocked): Promise
    {
        $resolver = new PromiseResolver();
        $this->database->onCompletion(
            function (Database $database) use ($player, $blocked, $resolver) {
                $database->unblockPlayer(
                    $player,
                    $blocked,
                    function () use ($player, $blocked, $resolver) {
                        // Update Block List Caches
                        $player = $this->listBlocked($player, true);
                        $blocked = $this->listBlockedBy($blocked, true);

                        $waitGroup = 2;
                        $onComplete = function () use (&$waitGroup, $resolver) {
                            --$waitGroup;
                            if (0 === $waitGroup) {
                                $resolver->resolve(null);
                            }
                        };

                        $player->onCompletion($onComplete, function () {
                        });
                        $blocked->onCompletion($onComplete, function () {
                        });
                    }
                );
            },
            function () {}
        );

        return $resolver->getPromise();
    }
}
