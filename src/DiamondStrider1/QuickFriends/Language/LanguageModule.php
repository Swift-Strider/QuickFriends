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

        $this->plugin->saveResource("languages/$locale.lang");
        $file = $this->plugin->getDataFolder()."languages/$locale.lang";
        $contents = file_get_contents($file);
        if (false === $contents) {
            $this->logger->error("Error reading language file: $file");
            $this->logger->error('file_get_contents(...) returned false!');

            return null;
        }
        $lines = preg_split('/\r?\n/', $contents);
        if (false === $lines) {
            $this->logger->error("Error parsing language file: $file");
            $this->logger->error('preg_split(...) returned false!');

            return null;
        }

        $entries = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '#')) {
                continue;
            }
            $components = explode('=', $line, 2);
            if (2 !== count($components)) {
                continue;
            }
            $entries[$components[0]] = $components[1];
        }

        $parser = new Parser(
            $entries,
            "%error_count% error(s) in languages/$locale.lang",
            '%error_count% error(s) in %element_name%'
        );
        try {
            return Language::parse($parser);
        } catch (ParseStopException $e) {
            if (
                $returnNullOnError ||
                ($entries['keep_file_edits'] ?? null) === 'true'
            ) {
                $message = $parser->generateErrorMessage();
                $this->logger->emergency("Failed to load language file, details below...\n$message");

                return null;
            }

            $this->plugin->saveResource("languages/$locale.lang", true);

            return $this->getLocaleLanguage($locale, true);
        }
    }
}
