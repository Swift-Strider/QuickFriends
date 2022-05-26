<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Language;

use DiamondStrider1\QuickFriends\Config\Parser;

final class Language
{
    public function __construct(
        private string $friend_request_sent,
        private string $friend_request_received,
        private string $friend_added,
        private string $friend_removed,
        private string $player_blocked,
        private string $player_unblocked,
    ) {
    }

    public static function parse(Parser $p): self
    {
        $friend_request_sent = $p->rString('friend_request_sent');
        $friend_request_received = $p->rString('friend_request_received');
        $friend_added = $p->rString('friend_added');
        $friend_removed = $p->rString('friend_removed');
        $player_blocked = $p->rString('player_blocked');
        $player_unblocked = $p->rString('player_unblocked');

        $p->check();

        return new self(
            $friend_request_sent->take(),
            $friend_request_received->take(),
            $friend_added->take(),
            $friend_removed->take(),
            $player_blocked->take(),
            $player_unblocked->take(),
        );
    }

    public function friend_request_sent(string $receiver, string $expireTime): string
    {
        return str_replace(
            ['%receiver%', '%expire_time%'],
            [$receiver, $expireTime],
            $this->friend_request_sent
        );
    }

    public function friend_request_received(string $sender, string $expireTime): string
    {
        return str_replace(
            ['%sender%', '%expire_time%'],
            [$sender, $expireTime],
            $this->friend_request_received
        );
    }

    public function friend_added(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->friend_added
        );
    }

    public function friend_removed(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->friend_removed
        );
    }

    public function player_blocked(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->player_blocked
        );
    }

    public function player_unblocked(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->player_unblocked
        );
    }
}
