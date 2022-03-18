<?php

namespace App\Service;

use App\Util\Result;
use App\Util\ResultTester;
use App\Util\Stats;
use App\Util\Wordle;
use App\Strategy\StrategyDecider;

class Solver
{
	public const MAX_GUESSES = 6;

	private StrategyDecider $strategyDecider;

	public function __construct(StrategyDecider $strategyDecider)
	{
		$this->strategyDecider = $strategyDecider;
	}

	public function solve(string $word, Wordle $wordle) : Wordle
	{
		$guessNumber = 1;
		$results = [];
		$found = false;

		while ($guessNumber <= self::MAX_GUESSES) {
			$wordle->setResults($results);

			$guess = $this->strategyDecider->getBestGuess($wordle);
			$result = ResultTester::getGuessResult($word, $guess->word);
			$results[] = $result;
			//echo sprintf("Guess %d: %s Result: %s Algo: %s\n", $guessNumber, $guess->word, $result, $guess->getAlgorithm());

			if ($result->isCorrect()) {
				break;
			}

			$guessNumber++;
		}

		return $wordle;

		return new TestResult($guessNumber, $word, $found, $totalTime);

		$primaryStrategy = StrategyDecider::getPrimaryStrategy($wordle, $this->database);

		$guess = $primaryStrategy->guess($wordle);

		if (!$guess) {
			// Fallback Strategy?
			throw new Exception('No Guess');
		}

		return $guess;
	}
}