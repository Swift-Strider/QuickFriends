<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Language;

use DiamondStrider1\QuickFriends\Config\Parser;

final class Language
{
    public function __construct(
        private string $command_unavailable,
        private string $invalid_target_self,
        private string $friend_request_sent,
        private string $friend_request_received,
        private string $friend_added,
        private string $friend_removed,
        private string $player_blocked_and_unfriended,
        private string $player_blocked,
        private string $player_unblocked,
        private string $join_succeeded,
        private string $join_failed,
        private string $join_not_friends,
        private string $list_friends_header,
        private string $list_friends_entry,
        private string $list_blocked_header,
        private string $list_blocked_entry,
        private string $friend_error_blocked,
        private string $friend_error_blocked_by,
        private string $friend_error_already_friends,
        private string $friend_error_limit_reached,
        private string $friend_error_other_limit_reached,
        private string $unfriend_error_not_friends,
        private string $block_error_already_blocked,
        private string $unblock_error_not_blocked,
    ) {
    }

    public static function parse(Parser $p): self
    {
        $command_unavailable = $p->rString('command_unavailable');
        $invalid_target_self = $p->rString('invalid_target_self');
        $friend_request_sent = $p->rString('friend_request_sent');
        $friend_request_received = $p->rString('friend_request_received');
        $friend_added = $p->rString('friend_added');
        $friend_removed = $p->rString('friend_removed');
        $player_blocked_and_unfriended = $p->rString('player_blocked_and_unfriended');
        $player_blocked = $p->rString('player_blocked');
        $player_unblocked = $p->rString('player_unblocked');
        $join_succeeded = $p->rString('join_succeeded');
        $join_failed = $p->rString('join_failed');
        $join_not_friends = $p->rString('join_not_friends');
        $list_friends_header = $p->rString('list_friends_header');
        $list_friends_entry = $p->rString('list_friends_entry');
        $list_blocked_header = $p->rString('list_blocked_header');
        $list_blocked_entry = $p->rString('list_blocked_entry');
        $friend_error_blocked = $p->rString('friend_error_blocked');
        $friend_error_blocked_by = $p->rString('friend_error_blocked_by');
        $friend_error_already_friends = $p->rString('friend_error_already_friends');
        $friend_error_limit_reached = $p->rString('friend_error_limit_reached');
        $friend_error_other_limit_reached = $p->rString('friend_error_other_limit_reached');
        $unfriend_error_not_friends = $p->rString('unfriend_error_not_friends');
        $block_error_already_blocked = $p->rString('block_error_already_blocked');
        $unblock_error_not_blocked = $p->rString('unblock_error_not_blocked');

        $p->check();

        return new self(
            $command_unavailable->take(),
            $invalid_target_self->take(),
            $friend_request_sent->take(),
            $friend_request_received->take(),
            $friend_added->take(),
            $friend_removed->take(),
            $player_blocked_and_unfriended->take(),
            $player_blocked->take(),
            $player_unblocked->take(),
            $join_succeeded->take(),
            $join_failed->take(),
            $join_not_friends->take(),
            $list_friends_header->take(),
            $list_friends_entry->take(),
            $list_blocked_header->take(),
            $list_blocked_entry->take(),
            $friend_error_blocked->take(),
            $friend_error_blocked_by->take(),
            $friend_error_already_friends->take(),
            $friend_error_limit_reached->take(),
            $friend_error_other_limit_reached->take(),
            $unfriend_error_not_friends->take(),
            $block_error_already_blocked->take(),
            $unblock_error_not_blocked->take(),
        );
    }

    public function command_unavailable(): string
    {
        return $this->command_unavailable;
    }

    public function invalid_target_self(): string
    {
        return $this->invalid_target_self;
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

    public function player_blocked_and_unfriended(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->player_blocked_and_unfriended
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

    public function join_succeeded(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->join_succeeded
        );
    }

    public function join_failed(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->join_failed
        );
    }

    public function join_not_friends(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->join_not_friends
        );
    }

    public function list_friends_header(string $count): string
    {
        return str_replace(
            '%count%',
            $count,
            $this->list_friends_header
        );
    }

    public function list_friends_entry(string $name, string $status): string
    {
        return str_replace(
            ['%name%', '%status%'],
            [$name, $status],
            $this->list_friends_entry
        );
    }

    public function list_blocked_header(string $count): string
    {
        return str_replace(
            '%count%',
            $count,
            $this->list_blocked_header
        );
    }

    public function list_blocked_entry(string $name, string $status): string
    {
        return str_replace(
            ['%name%', '%status%'],
            [$name, $status],
            $this->list_blocked_entry
        );
    }

    public function friend_error_blocked(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->friend_error_blocked
        );
    }

    public function friend_error_blocked_by(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->friend_error_blocked_by
        );
    }

    public function friend_error_already_friends(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->friend_error_already_friends
        );
    }

    public function friend_error_limit_reached(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->friend_error_limit_reached
        );
    }

    public function friend_error_other_limit_reached(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->friend_error_other_limit_reached
        );
    }

    public function unfriend_error_not_friends(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->unfriend_error_not_friends
        );
    }

    public function block_error_already_blocked(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->block_error_already_blocked
        );
    }

    public function unblock_error_not_blocked(string $other): string
    {
        return str_replace(
            '%other%',
            $other,
            $this->unblock_error_not_blocked
        );
    }
}
