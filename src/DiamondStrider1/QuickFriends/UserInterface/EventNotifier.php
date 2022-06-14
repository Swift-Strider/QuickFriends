<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\UserInterface;

use DiamondStrider1\QuickFriends\Event\FriendAddedEvent;
use DiamondStrider1\QuickFriends\Event\FriendRemovedEvent;
use DiamondStrider1\QuickFriends\Event\FriendRequestEvent;
use DiamondStrider1\QuickFriends\Event\PlayerBlockedEvent;
use DiamondStrider1\QuickFriends\Event\PlayerUnblockedEvent;
use DiamondStrider1\QuickFriends\Language\LanguageModule;
use DiamondStrider1\QuickFriends\Social\SocialModule;
use DiamondStrider1\QuickFriends\Social\SocialPlayerApi;
use DiamondStrider1\QuickFriends\Structures\Friendship;
use Generator;
use LogicException;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;
use SOFe\AwaitGenerator\Await;

final class EventNotifier implements Listener
{
    public function __construct(
        private LanguageModule $languageModule,
        private SocialModule $socialModule,
        private UserInterfaceConfig $userInterfaceConfig,
    ) {
    }

    /**
     * @priority MONITOR
     */
    public function onPlayerJoin(PlayerJoinEvent $ev): void
    {
        if (!$this->userInterfaceConfig->notifyFriendJoin()) {
            return;
        }
        $player = $ev->getPlayer();
        Await::f2c(function () use ($player): Generator { // @phpstan-ignore-line
            /** @var SocialPlayerApi $api */
            $api = yield from $this->socialModule->getSocialPlayerApi();
            $handle = $api->getPlayerHandle($player);
            /** @var Friendship[] $friends */
            $friends = yield from $api->listFriends($handle);
            if (!$player->isConnected()) {
                return;
            }
            foreach ($friends as $friend) {
                $other = match (true) {
                    $friend->requester()->uuid() === $handle->uuid() => $friend->accepter(),
                    $friend->accepter()->uuid() === $handle->uuid() => $friend->requester(),
                    default => throw new LogicException('The player must be either the requester or accepter of this Friendship!'),
                };
                $otherPlayer = $api->getPlayer($other);
                if (null !== $otherPlayer) {
                    $lang = $this->languageModule->getPlayerLanguage($otherPlayer);
                    $otherPlayer->sendMessage($lang->friend_joined($player->getName()));
                }
            }
        });
    }

    /**
     * @priority LOWEST
     */
    public function onPlayerQuit(PlayerQuitEvent $ev): void
    {
        if (!$this->userInterfaceConfig->notifyFriendQuit()) {
            return;
        }
        $player = $ev->getPlayer();
        Await::f2c(function () use ($player): Generator { // @phpstan-ignore-line
            /** @var SocialPlayerApi $api */
            $api = yield from $this->socialModule->getSocialPlayerApi();
            $handle = $api->getPlayerHandle($player);
            /** @var Friendship[] $friends */
            $friends = yield from $api->listFriends($handle);
            foreach ($friends as $friend) {
                $other = match (true) {
                    $friend->requester()->uuid() === $handle->uuid() => $friend->accepter(),
                    $friend->accepter()->uuid() === $handle->uuid() => $friend->requester(),
                    default => throw new LogicException('The player must be either the requester or accepter of this Friendship!'),
                };
                $otherPlayer = $api->getPlayer($other);
                if (null !== $otherPlayer) {
                    $lang = $this->languageModule->getPlayerLanguage($otherPlayer);
                    $otherPlayer->sendMessage($lang->friend_quit($player->getName()));
                }
            }
        });
    }

    public function friendRequestEvent(FriendRequestEvent $ev): void
    {
        $request = $ev->getRequest();
        $requester = $request->requester();
        $receiver = $request->receiver();

        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            $uuid = $p->getUniqueId()->getHex()->toString();
            if ($uuid === $requester->uuid()) {
                $lang = $this->languageModule->getPlayerLanguage($p);
                $p->sendMessage($lang->friend_request_sent(
                    $receiver->username(), TimeFormat::secondsToString($ev->getExpireTime()),
                ));
            } elseif ($uuid === $receiver->uuid()) {
                $lang = $this->languageModule->getPlayerLanguage($p);
                $p->sendMessage($lang->friend_request_received(
                    $requester->username(), TimeFormat::secondsToString($ev->getExpireTime()),
                ));
            }
        }
    }

    public function friendAddedEvent(FriendAddedEvent $ev): void
    {
        $friendship = $ev->getFriendship();
        $requester = $friendship->requester();
        $accepter = $friendship->accepter();

        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            $uuid = $p->getUniqueId()->getHex()->toString();
            if ($uuid === $requester->uuid()) {
                $lang = $this->languageModule->getPlayerLanguage($p);
                $p->sendMessage($lang->friend_added(
                    $accepter->username(),
                ));
            } elseif ($uuid === $accepter->uuid()) {
                $lang = $this->languageModule->getPlayerLanguage($p);
                $p->sendMessage($lang->friend_added(
                    $requester->username(),
                ));
            }
        }
    }

    public function friendRemovedEvent(FriendRemovedEvent $ev): void
    {
        $friendship = $ev->getPreviousFriendship();
        $requester = $friendship->requester();
        $accepter = $friendship->accepter();

        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            $uuid = $p->getUniqueId()->getHex()->toString();
            if ($uuid === $requester->uuid()) {
                $lang = $this->languageModule->getPlayerLanguage($p);
                $p->sendMessage($lang->friend_removed(
                    $accepter->username(),
                ));
            } elseif ($uuid === $accepter->uuid()) {
                $lang = $this->languageModule->getPlayerLanguage($p);
                $p->sendMessage($lang->friend_removed(
                    $requester->username(),
                ));
            }
        }
    }

    public function playerBlockedEvent(PlayerBlockedEvent $ev): void
    {
        $blockRelation = $ev->getBlockRelation();
        $player = $blockRelation->player();
        $blocked = $blockRelation->blocked();

        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            $uuid = $p->getUniqueId()->getHex()->toString();
            if ($uuid === $player->uuid()) {
                $lang = $this->languageModule->getPlayerLanguage($p);
                $p->sendMessage(match ($ev->werePreviouslyFriended()) {
                    true => $lang->player_blocked_and_unfriended(
                        $blocked->username(),
                    ),
                    false => $lang->player_blocked(
                        $blocked->username(),
                    )
                });
            } elseif ($uuid === $blocked->uuid() && $ev->werePreviouslyFriended()) {
                $lang = $this->languageModule->getPlayerLanguage($p);
                $p->sendMessage($lang->friend_removed(
                    $player->username(),
                ));
            }
        }
    }

    public function playerUnblockedEvent(PlayerUnblockedEvent $ev): void
    {
        $blockRelation = $ev->getPreviousBlockRelation();
        $player = $blockRelation->player();
        $blocked = $blockRelation->blocked();

        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            $uuid = $p->getUniqueId()->getHex()->toString();
            if ($uuid === $player->uuid()) {
                $lang = $this->languageModule->getPlayerLanguage($p);
                $p->sendMessage($lang->player_unblocked(
                    $blocked->username(),
                ));
            }
        }
    }
}
