<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Database;

use Closure;
use DiamondStrider1\QuickFriends\Config\ConfigModule;
use DiamondStrider1\QuickFriends\Modules\InjectArgsTrait;
use DiamondStrider1\QuickFriends\Modules\Module;
use Logger;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

final class DatabaseModule implements Module
{
    use InjectArgsTrait;

    private DataConnector $connection;
    private Database $db;

    /**
     * @phpstan-var (Closure(Database): void)[]
     */
    private array $pendingDatabaseConsumers = [];

    public function __construct(
        Plugin $plugin,
        Logger $logger,
        ConfigModule $configModule,
    ) {
        if (!$plugin instanceof PluginBase) {
            throw new PluginException('Expected plugin to extend PluginBase!');
        }

        $config = $configModule->getConfig()->databaseConfig();
        $this->connection = libasynql::create($plugin, $config->getSettingsArray(), [
            'sqlite' => 'sqlite.sql',
            'mysql' => 'mysql.sql',
        ], false);
        $this->connection->setLogger($logger);
        $db = new Database($this->connection);
        $db->initialize(function () use ($db, $logger) {
            $logger->debug('Database Initialized!');
            $this->db = $db;
            foreach ($this->pendingDatabaseConsumers as $consumer) {
                $consumer($this->db);
            }
        });
    }

    /**
     * @phpstan-param Closure(Database): void $consumer
     */
    public function waitForDatabase(Closure $consumer): void
    {
        if (isset($this->db)) {
            ($consumer)($this->db);
        } else {
            $this->pendingDatabaseConsumers[] = $consumer;
        }
    }

    public function close(): void
    {
        if (isset($this->connection)) {
            $this->connection->close();
        }
    }
}
