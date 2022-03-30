<?php

namespace App\Util;

class Guess
{
    private string $word;
    private array $statistics;

    public function __construct(string $word, array $statistics = [])
    {
        $this->word = $word;
        $this->statistics = $statistics;
    }

    public function getWord(): string
    {
        return $this->word;
    }

    public function getStatistics(): array
    {
        return $this->statistics;
    }
}
