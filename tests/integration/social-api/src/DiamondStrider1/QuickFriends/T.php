<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends;

use pocketmine\utils\AssumptionFailedError;

final class T
{
    public static function assert(bool $assert, string $msg, mixed $data = null): void
    {
        if (!$assert) {
            echo "Assertion Failed! $msg\n";
            var_dump($data);
            throw new AssumptionFailedError($msg);
        }
    }
}
