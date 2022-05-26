<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Database;

use DiamondStrider1\QuickFriends\Config\ParsedValue;
use DiamondStrider1\QuickFriends\Config\Parser;
use DiamondStrider1\QuickFriends\Config\ParseStopException;

final class DatabaseConfig
{
    /**
     * @param array<string, mixed> $settings
     * @phpstan-param "sqlite"|"mysql" $type
     */
    public function __construct(
        private array $settings,
        private string $type,
        private bool $enableLogging,
    ) {
    }

    /**
     * @phpstan-return ParsedValue<self>
     */
    public static function parse(Parser $parser): ParsedValue
    {
        $type = $parser->rString('type');
        $workerLimit = $parser->rInt('worker-limit');
        $enableLogging = $parser->rBool('enable-logging');

        try {
            $type = $type->take();
            if ('sqlite' !== $type && 'mysql' !== $type) {
                $parser->error('Expected element "type" to be set to either "sqlite" or "mysql".');
                // So phpstan recognizes that $type is "sqlite"|"mysql".
                throw new ParseStopException();
            }

            $settings = [
                'type' => $type,
                'worker-limit' => $workerLimit->take(),
            ];

            switch ($type) {
                case 'sqlite':
                    $sqlite = $parser->traverse('sqlite');
                    $settings['sqlite'] = [
                        'file' => $sqlite->rString('file')->take(),
                    ];
                    break;
                case 'mysql':
                    $mysql = $parser->traverse('mysql');
                    $settings['mysql'] = [
                        'host' => $mysql->rString('host')->take(),
                        'username' => $mysql->rString('username')->take(),
                        'password' => $mysql->rString('password')->take(),
                        'schema' => $mysql->rString('schema')->take(),
                    ];
                    break;
            }

            return ParsedValue::value(new self(
                $settings,
                $type,
                $enableLogging->take()
            ));
        } catch (ParseStopException) {
            return ParsedValue::error('Could not parse Database Config.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettingsArray(): array
    {
        return $this->settings;
    }

    /**
     * @phpstan-return "sqlite"|"mysql"
     */
    public function type(): string
    {
        return $this->type;
    }

    public function enableLogging(): bool
    {
        return $this->enableLogging;
    }
}
