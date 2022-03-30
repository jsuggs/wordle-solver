<?php

namespace App\Strategy;

use App\Util\Guess;

class StrategyResults
{
    private Strategy $strategy;
    private Guess $guess;
    private array $results;

    public function __construct(Strategy $strategy, Guess $guess, array $results)
    {
        $this->strategy = $strategy;
        $this->guess = $guess;
        $this->results = $results;
    }

    public function getStrategy(): Strategy
    {
        return $this->strategy;
    }

    public function getGuess(): Guess
    {
        return $this->guess;
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
