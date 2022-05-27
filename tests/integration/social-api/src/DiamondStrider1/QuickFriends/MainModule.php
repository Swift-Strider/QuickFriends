<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends;

use DiamondStrider1\QuickFriends\Config\ConfigModule;
use DiamondStrider1\QuickFriends\Database\DatabaseModule;
use DiamondStrider1\QuickFriends\Language\LanguageModule;
use DiamondStrider1\QuickFriends\Modules\EmptyCloseTrait;
use DiamondStrider1\QuickFriends\Modules\InjectArgsTrait;
use DiamondStrider1\QuickFriends\Modules\Module;
use DiamondStrider1\QuickFriends\Social\SocialModule;
use DiamondStrider1\QuickFriends\Social\SocialPlayerApi;
use DiamondStrider1\QuickFriends\Structures\Friendship;
use DiamondStrider1\QuickFriends\Structures\PlayerHandle;
use DiamondStrider1\QuickFriends\UserInterface\UserInterfaceModule;
use Logger;
use pocketmine\Server;
use SOFe\AwaitGenerator\Await;

final class MainModule implements Module
{
    use InjectArgsTrait;
    use EmptyCloseTrait;

    public function __construct(
        Logger $logger,
        public ConfigModule $configModule,
        public DatabaseModule $databaseModule,
        public LanguageModule $languageModule,
        public SocialModule $socialModule,
        public UserInterfaceModule $userInterfaceModule,
    ) {
        Await::f2c(function () use ($logger, $socialModule) {
            $first = new PlayerHandle('uuid1', 'PlayerOne', 'Windows 10', $t = time());
            $second = new PlayerHandle('uuid2', 'PlayerTwo', 'Android', $t);
            $third = new PlayerHandle('uuid3', 'PlayerThree', 'Android', $t);

            /** @var SocialPlayerApi $api */
            $api = yield from $socialModule->getSocialPlayerApi();
            $logger->alert('Got SocialPlayerApi Instance!');

            $addFriends = function () use ($api, $first, $second, $logger) {
                $logger->notice("Adding players as friends!");
                $code = yield from $api->addFriend($first, $second);
                T::assert($code === SocialPlayerApi::FRIEND_RESULT_NOTIFIED, 'Expected FRIEND_RESULT_NOTIFIED', $code);
                $code = yield from $api->addFriend($second, $first);
                T::assert($code === SocialPlayerApi::FRIEND_RESULT_NOW_FRIENDS, 'Expected FRIEND_RESULT_NOW_FRIENDS', $code);
                $friends = yield from $api->listFriends($second);
                $count = count($friends);
                T::assert($count === 1, 'Expected to find one friend', $count);
                /** @var Friendship $friend */
                $friend = $friends[0];
                $r = $friend->requester()->uuid();
                $a = $friend->accepter()->uuid();
                T::assert($r === $first->uuid(), 'Expected requester to be first PlayerHandle', $r);
                T::assert($a === $second->uuid(), 'Expected accepter to be second PlayerHandle', $a);
            };

            yield from $addFriends();
            $logger->notice("Unfriending players!");
            $code = yield from $api->removeFriend($second, $first);
            T::assert($code === SocialPlayerApi::UNFRIEND_RESULT_NOW_REMOVED, "Expected UNFRIEND_RESULT_NOW_REMOVED", $code);
            $code = yield from $api->removeFriend($second, $first);
            T::assert($code === SocialPlayerApi::UNFRIEND_RESULT_NOT_FRIENDS, "Expected UNFRIEND_RESULT_NOT_FRIENDS", $code);

            yield from $addFriends();
            $logger->notice("Blocking players!");
            $code = yield from $api->blockPlayer($first, $second);
            T::assert($code === SocialPlayerApi::BLOCK_RESULT_ALSO_UNFRIENDED, "Expected BLOCK_RESULT_ALSO_UNFRIENDED", $code);
            $code = yield from $api->blockPlayer($first, $second);
            T::assert($code === SocialPlayerApi::BLOCK_RESULT_ALREADY_BLOCKED, "Expected BLOCK_RESULT_ALREADY_BLOCKED", $code);

            $logger->notice("Unblocking Players!");
            $code = yield from $api->unblockPlayer($first, $second);
            T::assert($code === SocialPlayerApi::UNBLOCK_RESULT_NOW_UNBLOCKED, "Expected UNBLOCK_RESULT_NOW_UNBLOCKED", $code);
            $code = yield from $api->unblockPlayer($first, $second);
            T::assert($code === SocialPlayerApi::UNBLOCK_RESULT_NOT_BLOCKED, "Expected UNBLOCK_RESULT_NOT_BLOCKED", $code);

            $logger->notice("Blocking Players Again!");
            $code = yield from $api->blockPlayer($first, $second);
            T::assert($code === SocialPlayerApi::BLOCK_RESULT_NOW_BLOCKED, "Expected BLOCK_RESULT_NOW_BLOCKED", $code);

            $logger->notice("Attempting to Friend Players!");
            $code = yield from $api->addFriend($first, $second);
            T::assert($code === SocialPlayerApi::FRIEND_RESULT_BLOCKED, "Expected FRIEND_RESULT_BLOCKED", $code);
            $code = yield from $api->addFriend($second, $first);
            T::assert($code === SocialPlayerApi::FRIEND_RESULT_BLOCKED_BY, "Expected FRIEND_RESULT_BLOCKED_BY", $code);

            $logger->notice("Unblocking, then attempting to friend over the limit!");
            $code = yield from $api->unblockPlayer($first, $second);
            T::assert($code === SocialPlayerApi::UNBLOCK_RESULT_NOW_UNBLOCKED, "Expected UNBLOCK_RESULT_NOW_UNBLOCKED", $code);
            $code = yield from $api->addFriend($first, $second);
            T::assert($code === SocialPlayerApi::FRIEND_RESULT_NOTIFIED, "Expected FRIEND_RESULT_NOTIFIED", $code);
            $code = yield from $api->addFriend($first, $third);
            T::assert($code === SocialPlayerApi::FRIEND_RESULT_NOTIFIED, "Expected FRIEND_RESULT_NOTIFIED", $code);
            $code = yield from $api->addFriend($second, $first);
            T::assert($code === SocialPlayerApi::FRIEND_RESULT_NOW_FRIENDS, "Expected FRIEND_RESULT_NOW_FRIENDS", $code);
            $code = yield from $api->addFriend($third, $first);
            T::assert($code === SocialPlayerApi::FRIEND_RESULT_OTHER_LIMIT_REACHED, "Expected FRIEND_RESULT_OTHER_LIMIT_REACHED", $code);

            $logger->alert('Test Successful!');

            Server::getInstance()->shutdown();
        }, null, fn ($error) => throw $error);
    }
}
