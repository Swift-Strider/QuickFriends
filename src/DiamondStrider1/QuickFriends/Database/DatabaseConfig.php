<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Database;

use DiamondStrider1\QuickFriends\Config\ParsedValue;
use DiamondStrider1\QuickFriends\Config\Parser;
use DiamondStrider1\QuickFriends\Config\ParseStopException;

final class DatabaseConfig
{
    /**
     * @phpstan-param array<string, mixed> $settings
     */
    public function __construct(
        private array $settings
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

        try {
            return ParsedValue::value(new self(
                [
                    'type' => $type->take(),
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
                ]
            ));
        } catch (ParseStopException) {
            return ParsedValue::error('Could not parse Database Config.');
        }
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getSettingsArray(): array
    {
        return $this->settings;
    }
}
