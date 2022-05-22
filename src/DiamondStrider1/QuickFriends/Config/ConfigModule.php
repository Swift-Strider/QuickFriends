<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Config;

use DiamondStrider1\QuickFriends\Modules\EmptyCloseTrait;
use DiamondStrider1\QuickFriends\Modules\InjectArgsTrait;
use DiamondStrider1\QuickFriends\Modules\Module;
use Logger;
use pocketmine\plugin\PluginBase;

final class ConfigModule implements Module
{
    use InjectArgsTrait;
    use EmptyCloseTrait;

    private MainConfig $config;

    public function __construct(
        PluginBase $plugin,
        Logger $logger,
    ) {
        $plugin->saveResource('config.yml');
        $data = yaml_parse_file($plugin->getDataFolder().'config.yml');
        $data = is_array($data) ? $data : [];
        $parser = new Parser(
            $data,
            '%error_count% error(s) in config.yml',
            '%error_count% error(s) in %element_name%'
        );

        try {
            $this->config = MainConfig::parse($parser);
        } catch (ParseStopException $e) {
            $message = $parser->generateErrorMessage();
            $logger->critical("Failed to load config, details below...\n$message");
            throw $e;
        }
    }

    public function getConfig(): MainConfig
    {
        return $this->config;
    }
}
