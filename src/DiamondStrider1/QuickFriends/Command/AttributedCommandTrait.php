<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command;

use AssertionError;
use DiamondStrider1\QuickFriends\Command\attributes\CommandGroup;
use DiamondStrider1\QuickFriends\Command\attributes\CommandSettings;
use ReflectionClass;
use ReflectionMethod;

/**
 * Implements OverloadedCommand via attributes defined on users.
 *
 * @see OverloadedCommand
 */
trait AttributedCommandTrait
{
    private ?CommandGroup $commandGroup = null;

    /** @var CommandOverload[] */
    private array $overloads;

    public function getCommandGroup(): CommandGroup
    {
        // Finds the CommandGroup that decorates the using class.
        $this->commandGroup ??= ((new ReflectionClass(static::class))
            ->getAttributes(CommandGroup::class)[0] ?? null)
            ?->newInstance();

        if (null === $this->commandGroup) {
            throw new AssertionError(static::class.' is missing a CommandGroup attribute');
        }

        return $this->commandGroup;
    }

    /** @return CommandOverload[] */
    public function getOverloads(): array
    {
        if (isset($this->overloads)) {
            return $this->overloads;
        }

        $rMethods = (new ReflectionClass(static::class))->getMethods(ReflectionMethod::IS_PUBLIC);
        $overloads = [];
        foreach ($rMethods as $m) {
            $rAttr = $m->getAttributes(CommandSettings::class)[0] ?? null;
            if (null === $rAttr) {
                continue;
            }
            $s = $rAttr->newInstance();

            $overloads[] = new CommandOverload(
                $s,
                $m,
                $this
            );
        }

        return $this->overloads = $overloads;
    }
}
