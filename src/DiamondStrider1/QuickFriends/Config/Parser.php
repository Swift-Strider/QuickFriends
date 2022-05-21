<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Config;

use LogicException;

/**
 * An API for extracting values out of an array,
 * and reporting collected errors.
 *
 * * r<Type>() -> Report Error if value is of wrong type
 * * t<Type>() -> Don't report error
 */
final class Parser
{
    private ErrorTree $errorTree;

    /**
     * @param array<string, mixed> $data
     * @param string               $labelTemplate          %error_count% is substituted
     * @param string               $subParserLabelTemplate %error_count% and %element_name% is substituted
     */
    public function __construct(
        private array $data,
        string $labelTemplate,
        private string $subParserLabelTemplate,
    ) {
        $this->errorTree = new ErrorTree($labelTemplate);
    }

    /**
     * @phpstan-return ParsedValue<string>
     */
    public function rString(string $key): ParsedValue
    {
        return $this->expectElement($key, 'string', 'is_string');
    }

    /**
     * @phpstan-return ParsedValue<int>
     */
    public function rInt(string $key): ParsedValue
    {
        return $this->expectElement($key, 'integer', 'is_int');
    }

    /**
     * @phpstan-return ParsedValue<float>
     */
    public function rFloat(string $key): ParsedValue
    {
        return $this->expectElement($key, 'double (decimal number)', 'is_numeric');
    }

    /**
     * @phpstan-return ParsedValue<bool>
     */
    public function rBool(string $key): ParsedValue
    {
        return $this->expectElement($key, 'boolean', 'is_bool');
    }

    /**
     * @phpstan-return ParsedValue<array>
     * @phpstan-ignore-next-line
     */
    public function rList(string $key, string $typeName, callable $typeCheck): ParsedValue
    {
        if (!isset($this->data[$key])) {
            return $this->r(
                ParsedValue::error("Element \"$key\" is missing, it must be type `$typeName`.")
            );
        }

        $list = $this->data[$key];
        if (!is_array($list)) {
            $trueType = gettype($list);

            return $this->r(
                ParsedValue::error("Expected element \"$key\" to be `$typeName`, but it is `$trueType`.")
            );
        }

        $expected = 0;
        foreach ($list as $index => $element) {
            if ($index !== $expected) {
                return $this->r(
                    ParsedValue::error("Expected element \"$key\" to be `$typeName`, but index $index did not match expected $expected.")
                );
            }
            if (!($typeCheck)($element)) {
                return $this->r(
                    ParsedValue::error("Expected element \"$key\" to be `$typeName`, but value at index $index does not adhere to this list's type.")
                );
            }
            ++$expected;
        }

        return ParsedValue::value($list);
    }

    /**
     * @phpstan-return ParsedValue<string>
     */
    public function rAny(string $key): ParsedValue
    {
        return $this->expectElement($key, '<any>', fn (mixed $value): bool => true);
    }

    /**
     * Returns a new parser scoped to the array element specified by key.
     */
    public function traverse(string $key): self
    {
        $data = $this->data[$key] ?? [];
        $data = is_array($data) ? $data : [];
        $newLabelTemplate = str_replace(
            '%element_name%',
            $key,
            $this->subParserLabelTemplate
        );

        $subParser = new self($data, $newLabelTemplate, $this->subParserLabelTemplate);
        $this->errorTree->addSubErrorTree($subParser->errorTree);

        if (!is_array($this->data[$key] ?? null)) {
            $subParser->errorTree->addError('Expected a `dictionary` (array/indent).');
        }

        return $subParser;
    }

    /**
     * Fetches key and checks its type, reporting (not throwing)
     * an error if it fails.
     *
     * @phpstan-param callable(mixed $value): bool $typeCheck
     * @phpstan-ignore-next-line does not specify return type generics
     */
    private function expectElement(string $key, string $typeName, callable $typeCheck): ParsedValue
    {
        if (!isset($this->data[$key])) {
            return $this->r(
                ParsedValue::error("Element \"$key\" is missing, it must be type `$typeName`.")
            );
        }

        $value = $this->data[$key];
        if (!($typeCheck)($value)) {
            $trueType = gettype($value);

            return $this->r(
                ParsedValue::error("Expected element \"$key\" to be `$typeName`, but it is `$trueType`.")
            );
        }

        return ParsedValue::value($value);
    }

    /**
     * Reports an error stored in a ParsedValue.
     *
     * @phpstan-ignore-next-line
     */
    private function r(ParsedValue $parsed): ParsedValue
    {
        if (null === ($error = $parsed->getError())) {
            throw new LogicException('Attempt to report a null error');
        }

        $this->errorTree->addError($error);

        return $parsed;
    }

    public function error(string $error): void
    {
        $this->errorTree->addError($error);
    }

    /**
     * Checks to see if there were any errors generated so far.
     *
     * @throws ParseStopException when there is at least one error
     */
    public function check(): void
    {
        if (!$this->errorTree->isEmpty()) {
            throw new ParseStopException("Parser was check()'d and errors were found!");
        }
    }

    public function generateErrorMessage(
        string $indentString = '  ',
        int $indentLevel = 0
    ): string {
        return $this->errorTree->toString();
    }
}
