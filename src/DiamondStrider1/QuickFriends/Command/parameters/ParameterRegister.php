<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command\parameters;

use pocketmine\player\Player;

final class ParameterRegister
{
    /** @var array<string, class-string<CommandParameter>> */
    private static array $typeMap = [];

    /**
     * @param class-string<CommandParameter> $paramClass
     */
    public static function register(string $type, string $paramClass): void
    {
        self::$typeMap[$type] = $paramClass;
    }

    public static function get(string $type, bool $optional): ?CommandParameter
    {
        if (0 === count(self::$typeMap)) {
            self::register('string', StringParameter::class);
            self::register('int', IntParameter::class);
            self::register('float', FloatParameter::class);
            self::register(Player::class, PlayerParameter::class);
        }

        $class = self::$typeMap[$type] ?? null;
        if (null !== $class) {
            return new $class($optional);
        }

        return null;
    }
}
