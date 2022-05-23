<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Social;

use DiamondStrider1\QuickFriends\Config\ConfigModule;
use DiamondStrider1\QuickFriends\Database\DatabaseModule;
use DiamondStrider1\QuickFriends\Modules\EmptyCloseTrait;
use DiamondStrider1\QuickFriends\Modules\InjectArgsTrait;
use DiamondStrider1\QuickFriends\Modules\Module;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

final class SocialModule implements Module
{
    use InjectArgsTrait;
    use EmptyCloseTrait;

    private SocialPlayerApi $socialPlayerApi;

    public function __construct(
        PluginBase $plugin,
        ConfigModule $configModule,
        DatabaseModule $databaseModule,
    ) {
        $socialConfig = $configModule->getConfig()->socialConfig();
        $userPreferencesConfig = $configModule->getConfig()->userPreferencesConfig();

        $socialDao = new SocialDao(
            $databaseModule->getDatabase(), $userPreferencesConfig
        );
        $socialRuntime = new SocialRuntime($socialConfig);

        $this->socialPlayerApi = new SocialPlayerApi(
            $socialDao,
            $socialRuntime,
            $socialConfig,
        );

        $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function () use ($socialRuntime): void {
                $socialRuntime->clearExpired();
            }
        ), $socialConfig->friendRequestDuration());

        $pm = $plugin->getServer()->getPluginManager();
        $pm->registerEvent(
            PlayerQuitEvent::class,
            function (PlayerQuitEvent $ev) use ($socialRuntime) {
                $uuid = $ev->getPlayer()->getUniqueId()->getHex()->toString();
                $requests = $socialRuntime->getAllFriendRequests();
                foreach ($requests as $request) {
                    if ($request->requester() === $uuid || $request->receiver() === $uuid) {
                        // Invalidate the friend request to cleaned up.
                        $request->claimed = true;
                    }
                }
            },
            EventPriority::MONITOR, $plugin, true
        );
    }

    public function getSocialPlayerApi(): SocialPlayerApi
    {
        return $this->socialPlayerApi;
    }
}
