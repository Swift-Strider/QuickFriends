<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Modules;

final class ModuleUtils
{
    public static function getModuleName(string $moduleClass): string
    {
        $start = strrpos($moduleClass, '\\', -1);
        $end = strrpos($moduleClass, 'Module', -1);

        if (false === $start) {
            $start = 0;
        } else {
            ++$start;
        }
        if (false === $end) {
            $end = strlen($moduleClass);
        }

        return substr($moduleClass, $start, $end - $start);
    }
}
