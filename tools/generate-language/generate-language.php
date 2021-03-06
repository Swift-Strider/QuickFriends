<?php

declare(strict_types=1);

$lookupLang = 'en_US';
$langs = glob('resources/languages/*');
if ($langs === false) {
    fwrite(STDERR, "An error occurred with glob()\n");
    exit(1);
}
$langs = array_map(fn($f) => str_replace(['resources/languages/', '.yml'], '', $f), $langs);
if (count($langs) < 1) {
    fwrite(STDERR, "Assertion failed: count(\$langs) < 1");
    exit(1);
}

$dryRun = ($argv[1] ?? '') === '--dry-run';

$langInfoLangs = array_map(fn($l) => "'$l' => true,", $langs);
$langInfoLangs = implode("\n        ", $langInfoLangs);
$langInfoClass = <<<EOT
<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Language;

/**
 * This class was auto-generated by
 * `tools/language/generate-language.php`.
 */
final class LanguageInfo
{
    public const ALL_LANGS = [
        $langInfoLangs
    ];
}

EOT;

$rawEntries = yaml_parse_file("resources/languages/$lookupLang.yml");
if ($rawEntries === false) {
    fwrite(STDERR, "An error occurred with yaml_parse_file()\n");
    exit(1);
}
if (!is_array($rawEntries)) {
    fwrite(STDERR, "Assertion failed: !is_array(\$entries)");
    exit(1);
}
$entries = [];
foreach ($rawEntries as $name => $value) {
    if ($name !== 'keep_file_edits') {
        $entries[$name] = $value;
    }
}

$languageParams = array_map(fn($n) => "private string \$$n,", array_keys($entries));
$languageParams = implode("\n        ", $languageParams);
$languageParse = array_map(fn($n) => "\$$n = \$p->rString('$n');", array_keys($entries));
$languageParse = implode("\n        ", $languageParse);
$languageTake = array_map(fn($n) => "\${$n}->take(),", array_keys($entries));
$languageTake = implode("\n            ", $languageTake);

$languageMethods = "";
foreach ($entries as $name => $value) {
    $count = preg_match_all('/\%(\w*)\%/', $value, $matches);
    if ($count === false) {
        fwrite(STDERR, "An error occurred with preg_match_all()\n");
        exit(1);
    }
    if ($count === 0) {
        $languageMethods .= "\n\n" . <<<EOT
            public function $name(): string
            {
                return \$this->$name;
            }
        EOT;
        continue;
    }
    $matches = array_unique($matches[1]);
    sort($matches);
    $params = implode(', ', array_map(fn($m) => "string \$$m", $matches));
    $replace = implode(', ', array_map(fn($m) => "'%$m%'", $matches));
    $replaceParams = implode(', ', array_map(fn($m) => "\$$m", $matches));

    if (count($matches) === 1) {
        $languageMethods .= "\n\n" . <<<EOT
            public function $name($params): string
            {
                return str_replace(
                    $replace,
                    $replaceParams,
                    \$this->$name
                );
            }
        EOT;
    } else {
        $languageMethods .= "\n\n" . <<<EOT
            public function $name($params): string
            {
                return str_replace(
                    [$replace],
                    [$replaceParams],
                    \$this->$name
                );
            }
        EOT;
    }
}

$languageClass = <<<EOT
<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Language;

use DiamondStrider1\QuickFriends\Config\Parser;

/**
 * This class was auto-generated by
 * `tools/language/generate-language.php`.
 */
final class Language
{
    public function __construct(
        $languageParams
    ) {
    }

    public static function parse(Parser \$p): self
    {
        $languageParse

        \$p->check();

        return new self(
            $languageTake
        );
    }$languageMethods
}

EOT;

$langInfoFile = 'src/DiamondStrider1/QuickFriends/Language/LanguageInfo.php';
$languageFile = 'src/DiamondStrider1/QuickFriends/Language/Language.php';

if ($dryRun) {
    $changedFiles = [];
    if (file_get_contents($langInfoFile) !== $langInfoClass) {
        $changedFiles[] = "LanguageInfo.php";
    }
    if (file_get_contents($languageFile) !== $languageClass) {
        $changedFiles[] = "Language.php";
    }
    if (count($changedFiles) > 0) {
        $changedFiles = implode(', ', $changedFiles);
        echo "The following files need to be regenerated: $changedFiles\n";
        exit(2);
    }
    echo "Files are generated as they should be!\n";
    exit(0);
}

file_put_contents($langInfoFile, $langInfoClass);
file_put_contents($languageFile, $languageClass);
