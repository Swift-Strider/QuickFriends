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

final class ModalForm
{
    use FormTrait;
    private string $content;
    private string $yesText = 'gui.yes';
    private string $noText = 'gui.no';

    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function yesText(string $yesText): self
    {
        $this->yesText = $yesText;

        return $this;
    }

    public function noText(string $noText): self
    {
        $this->noText = $noText;

        return $this;
    }

    /**
     * @phpstan-return Promise<bool>
     */
    public function sendPromise(Player $player): Promise
    {
        if (!isset($this->title) || !isset($this->content)) {
            throw new DomainException('Some required properties have not been set!');
        }

        $resolver = new PromiseResolver();
        $formData = [
            'type' => 'modal',
            'title' => $this->title,
            'content' => $this->content,
            'button1' => $this->yesText,
            'button2' => $this->noText,
        ];

        $validator = function ($data) {
            if (!\is_bool($data)) {
                throw new FormValidationException('Expected a response of type bool, got type '.\gettype($data).' instead!');
            }
        };

        $form = new InternalForm($formData, $resolver, $validator);

        $player->sendForm($form);

        return $resolver->getPromise();
    }

    /**
     * @phpstan-return Generator<mixed, mixed, mixed, bool>
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
