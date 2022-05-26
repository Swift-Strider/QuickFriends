<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Language;

use DiamondStrider1\QuickFriends\Config\ConfigModule;
use DiamondStrider1\QuickFriends\Config\Parser;
use DiamondStrider1\QuickFriends\Config\ParseStopException;
use DiamondStrider1\QuickFriends\Modules\EmptyCloseTrait;
use DiamondStrider1\QuickFriends\Modules\InjectArgsTrait;
use DiamondStrider1\QuickFriends\Modules\Module;
use Logger;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use RuntimeException;

final class LanguageModule implements Module
{
    use InjectArgsTrait;
    use EmptyCloseTrait;

    private Language $defaultLanguage;
    /**
     * @var array<string, Language>
     */
    private array $loadedLanguages = [];

    public function __construct(
        private PluginBase $plugin,
        private Logger $logger,
        ConfigModule $configModule,
    ) {
        $config = $configModule->getConfig()->languageConfig();
        $defaultLanguage = $this->getLocaleLanguage($config->defaultLanguage());
        if (null === $defaultLanguage) {
            $logger->critical('Failed to load default language, shutting down server.');
            throw new RuntimeException('Failed to load default language!');
        }
        $this->defaultLanguage = $defaultLanguage;
    }

    public function getPlayerLanguage(Player $player): Language
    {
        return $this->getLocaleLanguage($player->getLocale()) ?? $this->defaultLanguage;
    }

    public function getLocaleLanguage(
        string $locale, bool $returnNullOnError = false): ?Language
    {
        if (!isset(LanguageInfo::ALL_LANGS[$locale])) {
            return null;
        }

        if (null !== ($lang = $this->loadedLanguages[$locale] ?? null)) {
            return $lang;
        }

        $this->plugin->saveResource("languages/$locale.yml");
        $file = $this->plugin->getDataFolder()."languages/$locale.yml";

        $entries = yaml_parse_file($file);
        if (!is_array($entries)) {
            $this->logger->emergency("The language file is corrupted: $file");

            return null;
        }

        $parser = new Parser(
            $entries,
            "%error_count% error(s) in languages/$locale.yml",
            '%error_count% error(s) in %element_name%'
        );
        try {
            return Language::parse($parser);
        } catch (ParseStopException $e) {
            if (
                $returnNullOnError ||
                ($entries['keep_file_edits'] ?? null) === true
            ) {
                $message = $parser->generateErrorMessage();
                $this->logger->emergency("Failed to load language file, details below...\n$message");

                return null;
            }

            $this->plugin->saveResource("languages/$locale.yml", true);

            return $this->getLocaleLanguage($locale, true);
        }
    }
}
