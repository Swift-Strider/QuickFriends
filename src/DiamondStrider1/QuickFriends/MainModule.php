<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends;

use DiamondStrider1\QuickFriends\Config\ConfigModule;
use DiamondStrider1\QuickFriends\Database\DatabaseModule;
use DiamondStrider1\QuickFriends\Language\LanguageModule;
use DiamondStrider1\QuickFriends\Modules\EmptyCloseTrait;
use DiamondStrider1\QuickFriends\Modules\InjectArgsTrait;
use DiamondStrider1\QuickFriends\Modules\Module;
use DiamondStrider1\QuickFriends\Social\SocialModule;
use DiamondStrider1\QuickFriends\UserInterface\UserInterfaceModule;

final class MainModule implements Module
{
    use InjectArgsTrait;
    use EmptyCloseTrait;

    public function __construct(
        public ConfigModule $configModule,
        public DatabaseModule $databaseModule,
        public LanguageModule $languageModule,
        public SocialModule $socialModule,
        public UserInterfaceModule $userInterfaceModule,
    ) {
    }
}
