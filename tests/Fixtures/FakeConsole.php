<?php

namespace Pboivin\Logtool\Tests\Fixtures;

use Pboivin\Logtool\Console;

class FakeConsole extends Console
{
    public $output;

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->output = [];
    }

    public function getOutput(): string
    {
        return trim(implode('', $this->output));
    }

    public function echo(string $s): void
    {
        $this->output[] = $s;
    }

    public function input(string $prompt): string
    {
        throw new \Exception('Unexpected call to input()');
    }

    public function readCommand(): array
    {
        throw new \Exception('Unexpected call to readCommand()');
    }

    public function exit(int $code): void
    {
        throw new \Exception('Unexpected call to exit()');
    }

    public function isInteractive(): bool
    {
        return false;
    }
}
