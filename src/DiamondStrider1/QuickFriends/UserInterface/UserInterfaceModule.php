<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\UserInterface;

use DiamondStrider1\QuickFriends\Config\ConfigModule;
use DiamondStrider1\QuickFriends\Language\LanguageModule;
use DiamondStrider1\QuickFriends\Modules\EmptyCloseTrait;
use DiamondStrider1\QuickFriends\Modules\InjectArgsTrait;
use DiamondStrider1\QuickFriends\Modules\Module;
use DiamondStrider1\QuickFriends\Social\SocialModule;
use DiamondStrider1\Remark\Remark;
use pocketmine\plugin\PluginBase;

final class UserInterfaceModule implements Module
{
    use InjectArgsTrait;
    use EmptyCloseTrait;

    public function __construct(
        PluginBase $plugin,
        ConfigModule $configModule,
        LanguageModule $languageModule,
        SocialModule $socialModule,
    ) {
        $userInterfaceConfig = $configModule->getConfig()->userInterfaceConfig();
        $pluginManager = $plugin->getServer()->getPluginManager();

        $pluginManager->registerEvents(new EventNotifier($languageModule, $socialModule, $userInterfaceConfig), $plugin);
        Remark::command($plugin, new Commands($languageModule, $socialModule));
        Remark::activate($plugin);
    }
}
