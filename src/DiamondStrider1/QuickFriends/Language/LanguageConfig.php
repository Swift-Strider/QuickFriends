<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Language;

use DiamondStrider1\QuickFriends\Config\ParsedValue;
use DiamondStrider1\QuickFriends\Config\Parser;
use DiamondStrider1\QuickFriends\Config\ParseStopException;

final class LanguageConfig
{
    public function __construct(
        private string $defaultLanguage,
    ) {
    }

    /**
     * @phpstan-return ParsedValue<self>
     */
    public static function parse(Parser $parser): ParsedValue
    {
        $defaultLanguage = $parser->rString('default-language');

        try {
            $defaultLanguage = $defaultLanguage->take();
            if (!isset(LanguageInfo::ALL_LANGS[$defaultLanguage])) {
                $allLangs = implode(', ', array_keys(LanguageInfo::ALL_LANGS));
                $parser->error("The selected default language ($defaultLanguage) is not supported. Supported languages are: $allLangs");
            }

            return ParsedValue::value(new self(
                $defaultLanguage,
            ));
        } catch (ParseStopException) {
            return ParsedValue::error('Could not parse Language Config.');
        }
    }

    public function defaultLanguage(): string
    {
        return $this->defaultLanguage;
    }
}
