<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends;

use DiamondStrider1\QuickFriends\Modules\EmptyCloseTrait;
use DiamondStrider1\QuickFriends\Modules\InjectArgsTrait;
use DiamondStrider1\QuickFriends\Modules\Module;

final class MainModule implements Module
{
    use InjectArgsTrait;
    use EmptyCloseTrait;

    public function __construct(
    ) {
    }
}
