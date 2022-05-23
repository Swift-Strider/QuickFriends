<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

use DiamondStrider1\QuickFriends\Config\ParsedValue;
use DiamondStrider1\QuickFriends\Config\Parser;
use DiamondStrider1\QuickFriends\Config\ParseStopException;

final class UserPreferencesConfig
{
    public function __construct(
        private UserPreferences $defaultPreferences
    ) {
    }

    /**
     * @phpstan-return ParsedValue<self>
     */
    public static function parse(Parser $parser): ParsedValue
    {
        $default = $parser->traverse('default-preferences');
        $prefersText = $default->rBool('prefers-text');
        $osVisibility = $default->rString('os-visibility');
        $muteFriendRequests = $default->rBool('mute-friend-requests');

        try {
            $osVisibility = match ($osVisibility->take()) {
                'everyone' => UserPreferences::OS_VISIBILITY_EVERYONE,
                'friends' => UserPreferences::OS_VISIBILITY_FRIENDS,
                'nobody' => UserPreferences::OS_VISIBILITY_NOBODY,
                default => throw new ParseStopException('Undefined OS Visibility type')
            };

            return ParsedValue::value(new self(
                new UserPreferences(
                    $prefersText->take(),
                    $osVisibility,
                    $muteFriendRequests->take()
                )
            ));
        } catch (ParseStopException) {
            return ParsedValue::error('Could not parse Social Config.');
        }
    }

    public function defaultPreferences(): UserPreferences
    {
        return $this->defaultPreferences;
    }
}
