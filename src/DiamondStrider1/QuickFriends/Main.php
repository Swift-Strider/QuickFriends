<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends;

use DiamondStrider1\QuickFriends\Modules\Context;
use DiamondStrider1\QuickFriends\Modules\PluginContext;
use pocketmine\plugin\PluginBase;

final class Main extends PluginBase
{
    private Context $context;

    public function onEnable(): void
    {
        $this->context = PluginContext::fromPlugin($this);
        MainModule::get($this->context);
    }

    public function onDisable(): void
    {
        $this->context->close();
    }
}
