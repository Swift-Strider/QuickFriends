<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Command;

class CommandArgs
{
    private int $start = 0;
    private int $index = 0;

    /**
     * @param string[] $args
     */
    public function __construct(
        private array $args,
        private string $label,
    ) {
    }

    public function poll(): ?string
    {
        return $this->args[$this->index] ?? null;
    }

    public function take(): ?string
    {
        return $this->args[$this->index++] ?? null;
    }

    public function prepare(): void
    {
        $this->start = $this->index;
    }

    /**
     * @return never
     */
    public function fail(string $message): void
    {
        $begin = implode(' ', \array_slice(
            $this->args,
            0,
            $this->start
        ));
        $middle = implode(' ', \array_slice(
            $this->args,
            $this->start,
            $this->index - $this->start
        ));
        $end = implode(' ', \array_slice(
            $this->args,
            $this->index
        ));

        $newMessage = "§c/{$this->label} ";
        if (0 !== strlen($begin)) {
            $newMessage .= "{$begin} ";
        }
        $newMessage .= '§l>>>§r§c ';
        if (0 !== strlen($middle)) {
            $newMessage .= "{$middle} ";
        }
        $newMessage .= '§l<<<§r§c';
        if (0 !== strlen($end)) {
            $newMessage .= " {$end}";
        }
        $newMessage .= "\n§c{$message}";

        throw new ValidationException($newMessage);
    }
}
