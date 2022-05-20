<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends;

use DiamondStrider1\QuickFriends\Modules\PluginContext;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase
{
    public function onEnable(): void
    {
        $context = PluginContext::fromPlugin($this);
        MainModule::get($context);
    }
}
