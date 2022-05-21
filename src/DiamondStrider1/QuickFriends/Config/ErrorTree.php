<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Config;

/**
 * Represents a tree of errors.
 */
final class ErrorTree
{
    /**
     * @var string[]
     */
    private array $errors = [];
    /**
     * @var ErrorTree[]
     */
    private array $children = [];

    /**
     * @param string $labelTemplate %error_count% is substituted
     */
    public function __construct(
        private string $labelTemplate
    ) {
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function addSubErrorTree(self $errorTree): void
    {
        $this->children[] = $errorTree;
    }

    /**
     * Returns a filtered list of children with errors.
     *
     * @return ErrorTree[]
     */
    private function getNonEmptyChildren(): array
    {
        return array_filter(
            $this->children,
            fn (ErrorTree $child) => !$child->isEmpty()
        );
    }

    public function isEmpty(): bool
    {
        return 0 === count($this->errors) && 0 === count($this->getNonEmptyChildren());
    }

    public function getErrorCount(): int
    {
        $errorCount = count($this->errors);
        foreach ($this->children as $child) {
            $errorCount += $child->getErrorCount();
        }

        return $errorCount;
    }

    public function toString(string $indentString = '  ', int $indentLevel = 0): string
    {
        $errorChildren = $this->getNonEmptyChildren();
        $errorCount = (string) $this->getErrorCount();
        $indent = str_repeat($indentString, $indentLevel);
        $label = str_replace('%error_count%', $errorCount, $this->labelTemplate);
        $string = "$indent$label";

        $indent = str_repeat($indentString, $indentLevel + 1);
        foreach ($this->errors as $error) {
            $string .= "\n$indent$error";
        }
        foreach ($errorChildren as $child) {
            $error = $child->toString($indentString, $indentLevel + 1);
            $string .= "\n$error";
        }

        return $string;
    }
}
