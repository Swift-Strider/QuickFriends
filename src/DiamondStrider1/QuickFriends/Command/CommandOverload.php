<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command;

use ArrayIterator;
use AssertionError;
use DiamondStrider1\QuickFriends\Command\attributes\CommandSettings;
use DiamondStrider1\QuickFriends\Command\parameters\CommandParameter;
use DiamondStrider1\QuickFriends\Command\parameters\ParameterRegister;
use DiamondStrider1\QuickFriends\Form\CustomForm;
use Generator;
use InvalidArgumentException;
use Iterator;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use ReflectionMethod;
use ReflectionNamedType;
use SOFe\AwaitGenerator\Await;

class CommandOverload extends Command
{
    public const TYPE_ALL = 0;
    public const TYPE_PLAYER = 1;

    private int $type;

    /** @var CommandParameter[] */
    private array $params = [];

    public function __construct(
        CommandSettings $s,
        private ReflectionMethod $method,
        private OverloadedCommand $owner,
    ) {
        parent::__construct($s->getName(), $s->getDescription(), $s->getUsageMessage(), $s->getAliases());
        $this->setPermission($s->getPermission());

        $rParams = $method->getParameters();
        if (0 === \count($rParams)) {
            throw new InvalidArgumentException('$method takes no parameters!');
        }
        $first = $rParams[0]->getType();
        if (!$first instanceof ReflectionNamedType) {
            throw new InvalidArgumentException("\$method's first parameter does not take a single named type!");
        }
        $this->type = match ($first->getName()) {
            Player::class => self::TYPE_PLAYER,
            CommandSender::class => self::TYPE_ALL,
            default => throw new InvalidArgumentException("\$method's first parameter is not of type Player or CommandSender!"),
        };

        $defaultUsage = '';
        foreach ($rParams as $i => $p) {
            if (0 === $i) {
                continue;
            }
            $rType = $p->getType();
            if (!$rType instanceof ReflectionNamedType) {
                throw new InvalidArgumentException("\$method's parameter at index {$i} is not a single named type!");
            }
            $param = ParameterRegister::get($rType->getName(), $rType->allowsNull());
            if (null === $param) {
                throw new InvalidArgumentException("\$method's parameter at index {$i} is of an unregistered type!");
            }

            $pName = strtolower($p->getName());
            $pType = $param->getUsageType();
            $defaultUsage .= "<{$pName}: {$pType}> ";
            $this->params[] = $param;
        }

        if (null === $s->getUsageMessage() && '' !== $defaultUsage) {
            $this->setUsage(trim($defaultUsage));
        }
    }

    /**
     * @param string[] $args
     */
    public function execute(CommandSender $sender, string $label, array $args): void
    {
        if (!$this->testPermission($sender)) {
            return;
        }
        if (self::TYPE_PLAYER === $this->type && !$sender instanceof Player) {
            $sender->sendMessage('§4You must run this command as a player!');

            return;
        }

        $args = new CommandArgs($args, $label);
        $validParams = [$sender];
        $remainingParams = new ArrayIterator($this->params);

        try {
            while ($remainingParams->valid()) {
                $p = $remainingParams->current();
                $args->prepare();
                try {
                    $validParams[] = $p->get($args);
                } catch (ValidationException $e) {
                    if (!$p->isOptional()) {
                        throw $e;
                    }
                    $validParams[] = null;
                }
                $remainingParams->next();
            }
        } catch (ValidationException $e) {
            if (!$sender instanceof Player) {
                $sender->sendMessage($e->getMessage());

                return;
            }

            $this->promptPlayer($sender, $label, $validParams, $remainingParams, $e);

            return;
        }
        $return = $this->method->invokeArgs($this->owner, [$sender] + $validParams);
        if ($return instanceof Generator) {
            Await::g2c($return);
        }
    }

    /**
     * @param array<int, mixed>          $validParams
     * @param Iterator<CommandParameter> $remainingParams
     */
    private function promptPlayer(Player $sender, string $label, array $validParams, Iterator $remainingParams, ValidationException $e): void
    {
        $error = $e->getMessage();
        CustomForm::create()
            ->title("Running \"/{$label}\"")
            ->label('§c'.$e->getMessage())
            ->input('Fill in missing arguments here.')
            ->sendPromise($sender)
            ->onCompletion(function ($response) use ($sender, $label, $validParams, $remainingParams, $error) {
                $value = $response[1] ?? null;
                if (null === $value) {
                    $sender->sendMessage($error);

                    return;
                }
                if (!\is_string($value)) {
                    throw new AssertionError('CustomForm should have ensured that $value is a string!');
                }

                $args = new CommandArgs(explode(' ', $value), $label);

                try {
                    while ($remainingParams->valid()) {
                        $p = $remainingParams->current();
                        $args->prepare();
                        $validParams[] = $p->get($args);
                        $remainingParams->next();
                    }
                } catch (ValidationException $e) {
                    $this->promptPlayer($sender, $label, $validParams, $remainingParams, $e);

                    return;
                }
                $this->method->invokeArgs($this->owner, [$sender] + $validParams);
            }, function (): void {
            });
    }
}
