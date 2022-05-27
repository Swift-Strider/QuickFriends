<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command;

use DiamondStrider1\QuickFriends\Form\MenuForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

class MountedCommand extends Command implements PluginOwned
{
    /** @var array<string, CommandOverload> */
    private array $overloadMap;

    public function __construct(
        string $name,
        private OverloadedCommand $base,
        private Plugin $plugin,
        array $aliases = [],
    ) {
        parent::__construct($name, $base->getCommandGroup()->getDescription(), null, $aliases);

        $this->setPermission($base->getCommandGroup()->getPermission());

        $usage = '';
        foreach ($this->base->getOverloads() as $overload) {
            $this->overloadMap[$overload->getName()] = $overload;
            $oUsage = $overload->getUsage();
            if ($oUsage instanceof Translatable) {
                $oUsage = $oUsage->getText();
            }
            $usage .= "/{$name} {$overload->getName()} {$oUsage} OR ";
        }
        // Trim trailing ' OR '
        $this->setUsage(substr($usage, 0, \strlen($usage) - 4));
    }

    public function execute(CommandSender $sender, string $label, array $args): void
    {
        if (!$this->testPermission($sender)) {
            return;
        }

        $overloadName = array_shift($args);
        if (null === $overloadName || !isset($this->overloadMap[$overloadName])) {
            if (!$sender instanceof Player) {
                $sender->sendMessage('§cThat subcommand does not exist!');
                $sender->sendMessage('§cTry: '.implode(', ', array_keys($this->overloadMap)));

                return;
            }

            $this->promptPlayer($sender, $label, $args);

            return;
        }

        $overload = $this->overloadMap[$overloadName];
        $overload->execute($sender, "{$label} {$overloadName}", $args);
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->plugin;
    }

    /**
     * @param string[] $args
     */
    private function promptPlayer(Player $sender, string $label, array $args): void
    {
        $form = MenuForm::create()
            ->title("Running /{$label}")
            ->content("§cThat subcommand does not exist!\n\n§rTry one of these, instead.");

        $indexToNameMap = [];
        foreach (array_keys($this->overloadMap) as $name) {
            $indexToNameMap[] = $name;
            $form->button("§2{$name}");
        }
        $form->sendPromise($sender)
            ->onCompletion(
                function ($response) use ($sender, $label, $args, $indexToNameMap): void {
                    if (null === $response) {
                        $sender->sendMessage('§cThat subcommand does not exist!');
                        $sender->sendMessage('§cTry: '.implode(', ', array_keys($this->overloadMap)));

                        return;
                    }
                    // The player's response is validated by CustomForm API
                    $overloadName = $indexToNameMap[$response];
                    $overload = $this->overloadMap[$overloadName];
                    $overload->execute($sender, "{$label} {$overloadName}", $args);
                },
                function (): void {
                }
            );
    }
}
