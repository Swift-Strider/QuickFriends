<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Structures;

use DiamondStrider1\QuickFriends\Config\ParsedValue;
use DiamondStrider1\QuickFriends\Config\Parser;
use DiamondStrider1\QuickFriends\Config\ParseStopException;

final class WorldFilter
{
    /**
     * @param bool               $allowOrBlockList true => allow-list, false => block-list
     * @param array<int, string> $worlds           folder names of selected worlds
     */
    public function __construct(
        private bool $allowOrBlockList,
        private array $worlds
    ) {
    }

    /**
     * @phpstan-return ParsedValue<self>
     */
    public static function parse(Parser $parser): ParsedValue
    {
        $allowOrBlockList = $parser->rBool('enable-block-list');
        $worlds = $parser->rList('worlds', 'list of strings', 'is_string');

        try {
            return ParsedValue::value(new self(
                !$allowOrBlockList->take(),
                $worlds->take()
            ));
        } catch (ParseStopException) {
            return ParsedValue::error('Could not parse World Filter.');
        }
    }

    public function isFilteredOut(string $worldFolderName): bool
    {
        return $this->allowOrBlockList xor in_array($worldFolderName, $this->worlds, true);
    }
}
