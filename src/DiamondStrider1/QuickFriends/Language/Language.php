<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Language;

use DiamondStrider1\QuickFriends\Config\Parser;

final class Language
{
    public function __construct(
        private string $friend_request_sent,
        private string $friend_request_received,
    ) {
    }

    public static function parse(Parser $p): self
    {
        $friend_request_sent = $p->rString('friend_request_sent');
        $friend_request_received = $p->rString('friend_request_received');

        $p->check();

        return new self(
            $friend_request_sent->take(),
            $friend_request_received->take(),
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
}
