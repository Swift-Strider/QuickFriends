<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Social;

use Closure;
use DiamondStrider1\QuickFriends\Config\ConfigModule;
use DiamondStrider1\QuickFriends\Database\DatabaseModule;
use DiamondStrider1\QuickFriends\Modules\EmptyCloseTrait;
use DiamondStrider1\QuickFriends\Modules\InjectArgsTrait;
use DiamondStrider1\QuickFriends\Modules\Module;
use Generator;
use pocketmine\plugin\PluginBase;
use SOFe\AwaitGenerator\Await;

final class SocialModule implements Module
{
    use InjectArgsTrait;
    use EmptyCloseTrait;

    private SocialPlayerApi $socialPlayerApi;
    /**
     * @phpstan-var (Closure(SocialPlayerApi): void)[]
     */
    private array $socialPlayerApiConsumers = [];

    public function __construct(
        PluginBase $plugin,
        ConfigModule $configModule,
        DatabaseModule $databaseModule,
    ) {
        $socialConfig = $configModule->getConfig()->socialConfig();
        $userPreferencesConfig = $configModule->getConfig()->userPreferencesConfig();
        $socialRuntime = new SocialRuntime($socialConfig, $plugin);

        Await::f2c(function () use ($socialConfig, $userPreferencesConfig, $socialRuntime, $databaseModule) { // @phpstan-ignore-line
            $this->socialPlayerApi = new SocialPlayerApi(
                $socialRuntime,
                $socialConfig,
                $userPreferencesConfig,
                yield from $databaseModule->getDatabase(),
            );

            foreach ($this->socialPlayerApiConsumers as $consumer) {
                $consumer($this->socialPlayerApi);
            }
        });
    }

    public function tryGetSocialPlayerApi(): ?SocialPlayerApi
    {
        return $this->socialPlayerApi ?? null;
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, SocialPlayerApi>
     */
    public function getSocialPlayerApi(): Generator
    {
        if (isset($this->socialPlayerApi)) {
            return $this->socialPlayerApi;
        }

        // @phpstan-ignore-next-line
        return yield from Await::promise(function ($resolve) {
            $this->socialPlayerApiConsumers[] = $resolve;
        });
    }
}
