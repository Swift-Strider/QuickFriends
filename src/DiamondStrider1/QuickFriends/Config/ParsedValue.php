<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Config;

/**
 * A value pulled out of an array that
 * may not exist.
 *
 * @template T type of the value
 */
final class ParsedValue
{
    private function __construct(
        private mixed $value,
        private ?string $error
    ) {
    }

    /**
     * @phpstan-template NewT
     * @phpstan-param NewT $value
     * @phpstan-return self<NewT>
     */
    public static function value(mixed $value): self
    {
        return new self($value, null);
    }

    /**
     * @phpstan-param string $error
     * @phpstan-ignore-next-line no generics are needed for return type
     */
    public static function error(string $error): self
    {
        return new self(null, $error);
    }

    /**
     * Returns `true` when take() won't throw an error.
     */
    public function canTake(): bool
    {
        return null === $this->error;
    }

    /**
     * @phpstan-return T
     *
     * @throws ParseStopException when the value does not exist
     */
    public function take(): mixed
    {
        if (null !== $this->error) {
            throw new ParseStopException('Tried to take() a non-existent value!');
        }

        return $this->value;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
