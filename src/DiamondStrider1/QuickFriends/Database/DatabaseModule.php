<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Database;

use Closure;
use DiamondStrider1\QuickFriends\Config\ConfigModule;
use DiamondStrider1\QuickFriends\Modules\InjectArgsTrait;
use DiamondStrider1\QuickFriends\Modules\Module;
use Generator;
use Logger;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use SOFe\AwaitGenerator\Await;

final class DatabaseModule implements Module
{
    use InjectArgsTrait;

    private DataConnector $connection;
    private Database $database;
    /**
     * @phpstan-var (Closure(Database): void)[]
     */
    private array $databaseConsumers = [];

    public function __construct(
        PluginBase $plugin,
        Logger $logger,
        ConfigModule $configModule,
    ) {
        $config = $configModule->getConfig()->databaseConfig();
        $this->connection = libasynql::create($plugin, $config->getSettingsArray(), [
            'sqlite' => 'sqlite.sql',
            'mysql' => 'mysql.sql',
        ], false);

        if ($config->enableLogging()) {
            $this->connection->setLogger($logger);
        }

        $db = match ($config->type()) {
            'sqlite' => new SqliteDatabase($this->connection),
            'mysql' => new MySqlDatabase($this->connection),
        };

        Await::f2c(function () use ($db, $logger) { // @phpstan-ignore-line
            yield from $db->initialize();
            $logger->debug('Database Initialized!');
            $this->database = $db;
            foreach ($this->databaseConsumers as $consumer) {
                $consumer($db);
            }
        }, null, fn ($error) => throw $error);
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, Database>
     */
    public function getDatabase(): Generator
    {
        if (isset($this->database)) {
            return $this->database;
        }

        // @phpstan-ignore-next-line
        return yield from Await::promise(function ($resolve) {
            $this->databaseConsumers[] = $resolve;
        });
    }

    public function close(): void
    {
        if (isset($this->connection)) {
            $this->connection->close();
        }
    }
}
