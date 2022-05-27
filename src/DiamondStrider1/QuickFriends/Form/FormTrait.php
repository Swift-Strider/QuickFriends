<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Form;

trait FormTrait
{
    private string $title;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
