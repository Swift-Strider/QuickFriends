<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Modules;

interface Module
{
    public static function get(Context $context): static;

    public function close(): void;
}
