<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Form;

use DomainException;
use Generator;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use RuntimeException;
use SOFe\AwaitGenerator\Await;

final class MenuForm
{
    use FormTrait;
    private string $content;

    /** @var array<int, array{text: string, image?: array{type: string, data: string}}> */
    private array $buttons = [];

    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param 'path'|'url' $iconType
     */
    public function button(string $text, ?string $iconType = null, ?string $iconLocation = null): self
    {
        $button = ['text' => $text];
        if (null !== $iconType && null !== $iconLocation) {
            $button['image'] = [
                'type' => $iconType,
                'data' => $iconLocation,
            ];
        }
        $this->buttons[] = $button;

        return $this;
    }

    /**
     * @phpstan-return Promise<null|int>
     */
    public function sendPromise(Player $player): Promise
    {
        if (!isset($this->title) || !isset($this->content)) {
            throw new DomainException('Some required properties have not been set!');
        }

        $resolver = new PromiseResolver();
        $formData = [
            'type' => 'form',
            'title' => $this->title,
            'content' => $this->content,
            'buttons' => $this->buttons,
        ];

        $validator = function ($data) {
            if (!\is_int($data) && null !== $data) {
                throw new FormValidationException('Expected a response of type int or null, got type '.\gettype($data).' instead!');
            }
        };

        $form = new InternalForm($formData, $resolver, $validator);

        $player->sendForm($form);

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, null|int>
     */
    public function sendGenerator(Player $player): Generator
    {
        // @phpstan-ignore-next-line
        return yield from Await::promise(function ($resolve, $reject) use ($player) {
            $this->sendPromise($player)->onCompletion($resolve, static function () use ($reject) {
                $reject(new RuntimeException('Promise was rejected!'));
            });
        });
    }
}
