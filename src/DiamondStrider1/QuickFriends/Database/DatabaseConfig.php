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
        $sqlite = $parser->traverse('sqlite');
        $sqliteFile = $sqlite->rString('file');
        $mysql = $parser->traverse('mysql');
        $mysqlHost = $mysql->rString('host');
        $mysqlUsername = $mysql->rString('username');
        $mysqlPassword = $mysql->rString('password');
        $mysqlSchema = $mysql->rString('schema');
        $workerLimit = $parser->rInt('worker-limit');
        $enableLogging = $parser->rBool('enable-logging');

        try {
            $type = $type->take();
            if ('sqlite' !== $type && 'mysql' !== $type) {
                $parser->error('Expected element "type" to be set to either "sqlite" or "mysql".');
                // So phpstan recognizes that $type is "sqlite"|"mysql".
                throw new ParseStopException();
            }

            return ParsedValue::value(new self(
                [
                    'type' => $type,
                    'sqlite' => [
                        'file' => $sqliteFile->take(),
                    ],
                    'mysql' => [
                        'host' => $mysqlHost->take(),
                        'username' => $mysqlUsername->take(),
                        'password' => $mysqlPassword->take(),
                        'schema' => $mysqlSchema->take(),
                    ],
                    'worker-limit' => $workerLimit->take(),
                ],
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
