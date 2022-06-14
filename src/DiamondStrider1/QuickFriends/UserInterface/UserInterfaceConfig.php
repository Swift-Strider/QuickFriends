<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\UserInterface;

use DiamondStrider1\QuickFriends\Config\ParsedValue;
use DiamondStrider1\QuickFriends\Config\Parser;
use DiamondStrider1\QuickFriends\Config\ParseStopException;

final class UserInterfaceConfig
{
    public function __construct(
        private bool $notifyFriendJoin,
        private bool $notifyFriendQuit,
    ) {
    }

    /**
     * @phpstan-return ParsedValue<self>
     */
    public static function parse(Parser $parser): ParsedValue
    {
        $notifyFriendJoin = $parser->rBool('notify-friend-join');
        $notifyFriendQuit = $parser->rBool('notify-friend-quit');

        try {
            return ParsedValue::value(new self(
                $notifyFriendJoin->take(),
                $notifyFriendQuit->take(),
            ));
        } catch (ParseStopException) {
            return ParsedValue::error('Could not parse Language Config.');
        }
    }

    public function notifyFriendJoin(): bool
    {
        return $this->notifyFriendJoin;
    }

    public function notifyFriendQuit(): bool
    {
        return $this->notifyFriendQuit;
    }
}
