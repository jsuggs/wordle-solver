<?php

namespace App\Strategy;

use App\Util\Guess;
use App\Util\Wordle;

class OpeningStrategy extends DatabaseStrategy
{
    public function getResults(Wordle $wordle): StrategyResults
    {
        if (0 === count($wordle->getResults())) {
            $guess = new Guess($this, 'ROATES');

            return new StrategyResults($guess, []);
        }

        throw new NoGuessException('No Guess');
    }

    public function getName(): string
    {
        return 'Opening';
    }
}
