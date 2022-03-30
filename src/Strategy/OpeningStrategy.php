<?php

namespace App\Strategy;

use App\Util\Guess;
use App\Util\Wordle;

class OpeningStrategy extends DatabaseStrategy
{
    public function getResults(Wordle $wordle): StrategyResults
    {
        if (0 === count($wordle->getResults())) {
            $guess = new Guess('ROATE');

            return new StrategyResults($this, $guess, []);
        }

        throw new NoGuessException('No Guess');
    }

    public function getName(): string
    {
        return 'Opening Strategy';
    }

    public function getDescription(): string
    {
        return 'This strategy uses a static list of words based on analysis of word/letter distributions.';
    }
}
