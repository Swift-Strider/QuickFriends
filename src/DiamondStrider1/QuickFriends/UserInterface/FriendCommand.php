<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\UserInterface;

use DiamondStrider1\QuickFriends\Command\AttributedCommandTrait;
use DiamondStrider1\QuickFriends\Command\attributes\CommandGroup;
use DiamondStrider1\QuickFriends\Command\attributes\CommandSettings;
use DiamondStrider1\QuickFriends\Command\OverloadedCommand;
use DiamondStrider1\QuickFriends\Language\LanguageModule;
use DiamondStrider1\QuickFriends\Social\SocialModule;
use DiamondStrider1\QuickFriends\Social\SocialPlayerApi;
use Generator;
use InvalidArgumentException;
use pocketmine\player\Player;

#[CommandGroup(
    description: 'Manage ranks on your server!',
    permission: 'quickfriends.friends'
)]
class FriendCommand implements OverloadedCommand
{
    use AttributedCommandTrait;

    public function __construct(
        public LanguageModule $languageModule,
        public SocialModule $socialModule,
    ) {
    }

    #[CommandSettings(
        name: 'add',
        permission: 'quickfriends.friends.add',
        description: 'Add someone to your friends list!',
    )]
    public function add(Player $sender, Player $other): Generator
    {
        $lang = $this->languageModule->getPlayerLanguage($sender);
        if ($sender === $other) {
            $sender->sendMessage($lang->invalid_target_self());

            return;
        }
        $api = $this->socialModule->tryGetSocialPlayerApi();
        if (null === $api) {
            $sender->sendMessage($lang->command_unavailable());

            return;
        }

        try {
            $senderHandle = $api->getPlayerHandle($sender);
            $otherHandle = $api->getPlayerHandle($other);
        } catch (InvalidArgumentException) {
            $sender->sendMessage($lang->command_unavailable());

            return; // A player hasn't fully joined yet.
        }

        $code = yield from $api->addFriend($senderHandle, $otherHandle);
        $message = match ($code) {
            SocialPlayerApi::FRIEND_RESULT_BLOCKED => $lang->friend_error_blocked($other->getName()),
            SocialPlayerApi::FRIEND_RESULT_BLOCKED_BY => $lang->friend_error_blocked_by($other->getName()),
            SocialPlayerApi::FRIEND_RESULT_ALREADY_FRIENDS => $lang->friend_error_already_friends($other->getName()),
            SocialPlayerApi::FRIEND_RESULT_LIMIT_REACHED => $lang->friend_error_limit_reached($other->getName()),
            SocialPlayerApi::FRIEND_RESULT_OTHER_LIMIT_REACHED => $lang->friend_error_other_limit_reached($other->getName()),
            default => null, // Other cases are handled by EventNotifier
        };
        if (null !== $message) {
            $sender->sendMessage($message);
        }
    }

    #[CommandSettings(
        name: 'remove',
        permission: 'quickfriends.friends.remove',
        description: 'Remove a player from your friends list!',
        aliases: ['rm']
    )]
    public function remove(Player $sender, Player $other): Generator
    {
        $lang = $this->languageModule->getPlayerLanguage($sender);
        if ($sender === $other) {
            $sender->sendMessage($lang->invalid_target_self());

            return;
        }
        $api = $this->socialModule->tryGetSocialPlayerApi();
        if (null === $api) {
            $sender->sendMessage($lang->command_unavailable());

            return;
        }

        try {
            $senderHandle = $api->getPlayerHandle($sender);
            $otherHandle = $api->getPlayerHandle($other);
        } catch (InvalidArgumentException) {
            $sender->sendMessage($lang->command_unavailable());

            return; // A player hasn't fully joined yet.
        }

        $code = yield from $api->removeFriend($senderHandle, $otherHandle);
        $message = match ($code) {
            SocialPlayerApi::UNFRIEND_RESULT_NOT_FRIENDS => $lang->unfriend_error_not_friends($other->getName()),
            default => null, // Other cases are handled by EventNotifier
        };
        if (null !== $message) {
            $sender->sendMessage($message);
        }
    }

    #[CommandSettings(
        name: 'list',
        permission: 'quickfriends.friends.list',
        description: 'List the people on your friends list!',
    )]
    public function list(Player $sender): Generator
    {
        $lang = $this->languageModule->getPlayerLanguage($sender);
        $api = $this->socialModule->tryGetSocialPlayerApi();
        if (null === $api) {
            $sender->sendMessage($lang->command_unavailable());

            return;
        }

        try {
            $senderHandle = $api->getPlayerHandle($sender);
        } catch (InvalidArgumentException) {
            $sender->sendMessage($lang->command_unavailable());

            return; // A player hasn't fully joined yet.
        }

        $friends = yield from $api->listFriends($senderHandle);
        $sender->sendMessage($lang->list_friends_header((string) count($friends)));
        foreach ($friends as $f) {
            // @phpstan-ignore-next-line phpstan doesn't understand match(bool)
            $otherHandle = match ($f->requester()->uuid() === $senderHandle->uuid()) {
                true => $f->accepter(),
                false => $f->requester(),
            };
            $status = null !== $api->getPlayer($otherHandle) ? 'online' : 'offline';
            $sender->sendMessage($lang->list_friends_entry(
                $otherHandle->username(), $status,
            ));
        }
    }

    #[CommandSettings(
        name: 'listblocked',
        permission: 'quickfriends.friends.listblocked',
        description: 'List the people on your block list!',
    )]
    public function listblocked(Player $sender): Generator
    {
        $lang = $this->languageModule->getPlayerLanguage($sender);
        $api = $this->socialModule->tryGetSocialPlayerApi();
        if (null === $api) {
            $sender->sendMessage($lang->command_unavailable());

            return;
        }

        try {
            $senderHandle = $api->getPlayerHandle($sender);
        } catch (InvalidArgumentException) {
            $sender->sendMessage($lang->command_unavailable());

            return; // A player hasn't fully joined yet.
        }

        $blocked = yield from $api->listBlocked($senderHandle);
        $sender->sendMessage($lang->list_blocked_header((string) count($blocked)));
        foreach ($blocked as $f) {
            // @phpstan-ignore-next-line phpstan doesn't understand match(bool)
            $otherHandle = match ($f->player()->uuid() === $senderHandle->uuid()) {
                true => $f->player(),
                false => $f->blocked(),
            };
            $status = null !== $api->getPlayer($otherHandle) ? 'online' : 'offline';
            $sender->sendMessage($lang->list_blocked_entry(
                $otherHandle->username(), $status,
            ));
        }
    }

    #[CommandSettings(
        name: 'block',
        permission: 'quickfriends.friends.block',
        description: 'Block a player from friending you!',
    )]
    public function block(Player $sender, Player $other): Generator
    {
        $lang = $this->languageModule->getPlayerLanguage($sender);
        if ($sender === $other) {
            $sender->sendMessage($lang->invalid_target_self());

            return;
        }
        $api = $this->socialModule->tryGetSocialPlayerApi();
        if (null === $api) {
            $sender->sendMessage($lang->command_unavailable());

            return;
        }

        try {
            $senderHandle = $api->getPlayerHandle($sender);
            $otherHandle = $api->getPlayerHandle($other);
        } catch (InvalidArgumentException) {
            $sender->sendMessage($lang->command_unavailable());

            return; // A player hasn't fully joined yet.
        }

        $code = yield from $api->blockPlayer($senderHandle, $otherHandle);
        $message = match ($code) {
            SocialPlayerApi::BLOCK_RESULT_ALREADY_BLOCKED => $lang->block_error_already_blocked($other->getName()),
            default => null, // Other cases are handled by EventNotifier
        };
        if (null !== $message) {
            $sender->sendMessage($message);
        }
    }

    #[CommandSettings(
        name: 'unblock',
        permission: 'quickfriends.friends.unblock',
        description: 'Unblock a player from friending you!',
    )]
    public function unblock(Player $sender, Player $other): Generator
    {
        $lang = $this->languageModule->getPlayerLanguage($sender);
        if ($sender === $other) {
            $sender->sendMessage($lang->invalid_target_self());

            return;
        }
        $api = $this->socialModule->tryGetSocialPlayerApi();
        if (null === $api) {
            $sender->sendMessage($lang->command_unavailable());

            return;
        }

        try {
            $senderHandle = $api->getPlayerHandle($sender);
            $otherHandle = $api->getPlayerHandle($other);
        } catch (InvalidArgumentException) {
            $sender->sendMessage($lang->command_unavailable());

            return; // A player hasn't fully joined yet.
        }

        $code = yield from $api->unblockPlayer($senderHandle, $otherHandle);
        $message = match ($code) {
            SocialPlayerApi::UNBLOCK_RESULT_NOT_BLOCKED => $lang->unblock_error_not_blocked($other->getName()),
            default => null, // Other cases are handled by EventNotifier
        };
        if (null !== $message) {
            $sender->sendMessage($message);
        }
    }

    #[CommandSettings(
        name: 'join',
        permission: 'quickfriends.friends.join',
        description: 'Teleport to a friend!',
    )]
    public function join(Player $sender, Player $friend): Generator
    {
        $lang = $this->languageModule->getPlayerLanguage($sender);
        if ($sender === $friend) {
            $sender->sendMessage($lang->invalid_target_self());

            return;
        }
        $api = $this->socialModule->tryGetSocialPlayerApi();
        if (null === $api) {
            $sender->sendMessage($lang->command_unavailable());

            return;
        }

        $code = yield from $api->joinPlayer($sender, $friend);
        $sender->sendMessage(match ($code) {
            SocialPlayerApi::JOIN_RESULT_SUCCEEDED => $lang->join_succeeded($friend->getName()),
            SocialPlayerApi::JOIN_RESULT_FAILED => $lang->join_failed($friend->getName()),
            SocialPlayerApi::JOIN_RESULT_NOT_FRIENDS => $lang->join_not_friends($friend->getName()),
        });
    }
}
