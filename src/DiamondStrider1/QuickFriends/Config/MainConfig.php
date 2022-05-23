<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Config;

use DiamondStrider1\QuickFriends\Database\DatabaseConfig;
use DiamondStrider1\QuickFriends\Social\SocialConfig;
use DiamondStrider1\QuickFriends\Structures\UserPreferencesConfig;

final class MainConfig
{
    public function __construct(
        private SocialConfig $socialConfig,
        private UserPreferencesConfig $userPreferencesConfig,
        private DatabaseConfig $databaseConfig,
    ) {
    }

    public static function parse(Parser $parser): self
    {
        $socialConfig = SocialConfig::parse($parser->traverse('social'));
        $userPreferencesConfig = UserPreferencesConfig::parse($parser->traverse('preferences'));
        $databaseConfig = DatabaseConfig::parse($parser->traverse('database'));

        $parser->check();

        return new self(
            $socialConfig->take(),
            $userPreferencesConfig->take(),
            $databaseConfig->take(),
        );
    }

    public function socialConfig(): SocialConfig
    {
        return $this->socialConfig;
    }

    public function userPreferencesConfig(): UserPreferencesConfig
    {
        return $this->userPreferencesConfig;
    }

    public function databaseConfig(): DatabaseConfig
    {
        return $this->databaseConfig;
    }
}
