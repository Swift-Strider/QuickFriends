<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Database;

use DiamondStrider1\QuickFriends\Config\ConfigModule;
use DiamondStrider1\QuickFriends\Modules\InjectArgsTrait;
use DiamondStrider1\QuickFriends\Modules\Module;
use Logger;
use pocketmine\plugin\PluginBase;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

final class DatabaseModule implements Module
{
    use InjectArgsTrait;

    private DataConnector $connection;
    /** @phpstan-var Promise<Database> */
    private Promise $db;

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

        $dbResolver = new PromiseResolver();
        $this->db = $dbResolver->getPromise();

        $db = new Database($this->connection);
        $db->initialize(function () use ($db, $dbResolver, $logger) {
            $logger->debug('Database Initialized!');
            $dbResolver->resolve($db);
        });
    }

    /**
     * @phpstan-return Promise<Database>
     */
    public function getDatabase(): Promise
    {
        return $this->db;
    }

    public function close(): void
    {
        if (isset($this->connection)) {
            $this->connection->close();
        }
    }
}
