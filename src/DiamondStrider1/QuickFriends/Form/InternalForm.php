<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Form;

use Closure;
use pocketmine\form\Form;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\promise\PromiseResolver;

/**
 * @template TValue
 */
final class InternalForm implements Form
{
    /**
     * @param array<string, mixed> $formData
     * @phpstan-param PromiseResolver<TValue> $resolver
     * @phpstan-param Closure(mixed $data): void $validator
     */
    public function __construct(
        private array $formData,
        private PromiseResolver $resolver,
        private Closure $validator,
    ) {
    }

    public function handleResponse(Player $player, $data): void
    {
        try {
            ($this->validator)($data);
        } catch (FormValidationException $e) {
            $this->resolver->reject();

            throw $e;
        }

        $this->resolver->resolve($data);
    }

    public function jsonSerialize(): mixed
    {
        return $this->formData;
    }
}
