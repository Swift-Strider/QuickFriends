<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Social;

use DiamondStrider1\QuickFriends\Structures\FriendRequest;
use DiamondStrider1\QuickFriends\Structures\PlayerHandle;
use InvalidArgumentException;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

/**
 * Data related to the Social Module that need not be persisted.
 */
final class SocialRuntime
{
    /**
     * @var array<string, FriendRequest>
     */
    private array $friendRequests = [];
    /**
     * @var array<string, PlayerHandle>
     */
    private array $handles = [];
    /**
     * @var array<string, Player>
     */
    private array $players = [];

    public function __construct(
        private SocialConfig $socialConfig,
        private PluginBase $plugin,
    ) {
        foreach ($plugin->getServer()->getOnlinePlayers() as $p) {
            $this->onPlayerJoin($p);
        }
        $pluginManager = $plugin->getServer()->getPluginManager();
        $pluginManager->registerEvent(
            PlayerJoinEvent::class,
            function (PlayerJoinEvent $ev) {
                $this->onPlayerJoin($ev->getPlayer());
            },
            EventPriority::LOWEST, $plugin, true
        );
        $pluginManager->registerEvent(
            PlayerQuitEvent::class,
            function (PlayerQuitEvent $ev) {
                $uuid = $ev->getPlayer()->getUniqueId()->getHex()->toString();
                unset($this->handles[$uuid]);
                unset($this->players[$uuid]);
                foreach ($this->friendRequests as $id => $request) {
                    if ($request->requester()->uuid() === $uuid || $request->receiver()->uuid() === $uuid) {
                        unset($this->friendRequests[$id]);
                    }
                }
            },
            EventPriority::MONITOR, $plugin, true
        );
    }

    private function onPlayerJoin(Player $player): void
    {
        $time = time();
        $uuid = $player->getUniqueId()->getHex()->toString();
        $username = $player->getName();
        $os = $player->getPlayerInfo()->getExtraData()['DeviceOS'];
        $os = match ($os) {
            DeviceOS::ANDROID => 'Android',
            DeviceOS::IOS => 'iOS',
            DeviceOS::OSX => 'Apple Mac',
            DeviceOS::AMAZON => 'Amazon',
            DeviceOS::GEAR_VR => 'Gear VR',
            DeviceOS::HOLOLENS => 'Hololens',
            DeviceOS::WINDOWS_10 => 'Windows 10',
            DeviceOS::WIN32 => 'Win32',
            DeviceOS::DEDICATED => 'Dedicated',
            DeviceOS::TVOS => 'TVOS',
            DeviceOS::PLAYSTATION => 'PlayStation',
            DeviceOS::NINTENDO => 'Nintendo',
            DeviceOS::XBOX => 'XBox',
            DeviceOS::WINDOWS_PHONE => 'Windows Phone',
            default => '<unknown>',
        };
        $this->handles[$uuid] = new PlayerHandle($uuid, $username, $os, $time);
        $this->players[$uuid] = $player;
    }

    public function addFriendRequest(FriendRequest $friendRequest): int
    {
        $id = $friendRequest->requester()->uuid().':'.$friendRequest->receiver()->uuid();
        $this->friendRequests[$id] = $friendRequest;
        $expireTime = $this->socialConfig->friendRequestDuration();
        $this->plugin->getScheduler()->scheduleDelayedTask(
            new ClosureTask(function () use ($id) {
                unset($this->friendRequests[$id]);
            }),
            $expireTime * 20,
        );

        return $expireTime;
    }

    public function getFriendRequest(string $requester, string $receiver): ?FriendRequest
    {
        $id = $requester.':'.$receiver;
        $request = $this->friendRequests[$id] ?? null;

        return $request;
    }

    public function removeFriendRequest(string $requester, string $receiver): void
    {
        $id = $requester.':'.$receiver;
        unset($this->friendRequests[$id]);
    }

    public function getPlayer(PlayerHandle $player): ?Player
    {
        return $this->players[$player->uuid()] ?? null;
    }

    public function getPlayerHandle(Player $player): PlayerHandle
    {
        $uuid = $player->getUniqueId()->getHex()->toString();

        return $this->handles[$uuid]
            ?? throw new InvalidArgumentException("The handle for the player hasn't been registered yet!");
    }
}
