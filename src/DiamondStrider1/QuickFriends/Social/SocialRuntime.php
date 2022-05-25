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

    public function __construct(
        private SocialConfig $socialConfig,
        private PluginBase $plugin,
    ) {
        $pluginManager = $plugin->getServer()->getPluginManager();
        $pluginManager->registerEvent(
            PlayerJoinEvent::class,
            function (PlayerJoinEvent $ev) {
                $time = time();
                $uuid = $ev->getPlayer()->getUniqueId()->getHex()->toString();
                $username = $ev->getPlayer()->getName();
                $os = $ev->getPlayer()->getPlayerInfo()->getExtraData()['DeviceOS'];
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
            },
            EventPriority::LOWEST, $plugin, true
        );
        $pluginManager->registerEvent(
            PlayerQuitEvent::class,
            function (PlayerQuitEvent $ev) {
                $uuid = $ev->getPlayer()->getUniqueId()->getHex()->toString();
                unset($this->handles[$uuid]);
                foreach ($this->friendRequests as $id => $request) {
                    if ($request->requester() === $uuid || $request->receiver() === $uuid) {
                        unset($this->friendRequests[$id]);
                    }
                }
            },
            EventPriority::MONITOR, $plugin, true
        );
    }

    public function addFriendRequest(FriendRequest $friendRequest): void
    {
        $id = $friendRequest->requester().':'.$friendRequest->receiver();
        $this->friendRequests[$id] = $friendRequest;
        $this->plugin->getScheduler()->scheduleDelayedTask(
            new ClosureTask(function () use ($id) {
                unset($this->friendRequests[$id]);
            }),
            $this->socialConfig->friendRequestDuration() * 20,
        );
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

    public function getPlayerHandle(Player $player): PlayerHandle
    {
        $uuid = $player->getUniqueId()->getHex()->toString();

        return $this->handles[$uuid]
            ?? throw new InvalidArgumentException("The handle for the player hasn't been registered yet!");
    }
}
