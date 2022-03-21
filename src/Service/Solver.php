<?php

namespace App\Service;

use App\Strategy\StrategyDecider;
use App\Util\Result;
use App\Util\ResultTester;
use App\Util\Wordle;

class Solver
{
    public const MAX_GUESSES = 6;

    private StrategyDecider $strategyDecider;

    public function __construct(StrategyDecider $strategyDecider)
    {
        $this->strategyDecider = $strategyDecider;
    }

    public function solve(string $word, Wordle $wordle): Wordle
    {
        $guessNumber = 1;
        $results = [];
        $found = false;

        while ($guessNumber <= self::MAX_GUESSES) {
            $guess = $this->strategyDecider->getBestGuess($wordle);
            $result = ResultTester::getGuessResult($word, $guess->word);
            $results[] = $result;
            $wordle->setResults($results);

            // echo sprintf("Guess %d: %s Result: %s Algo: %s\n", $guessNumber, $guess->word, $result, $guess->getAlgorithm());

            if ($result->isCorrect()) {
                break;
            }

            ++$guessNumber;
        }

        return $wordle;
    }
}
