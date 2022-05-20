<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends;

use DiamondStrider1\QuickFriends\Modules\InjectArgsTrait;
use DiamondStrider1\QuickFriends\Modules\Module;

class MainModule implements Module
{
    use InjectArgsTrait;

    public function __construct(
    ) {
    }
}
