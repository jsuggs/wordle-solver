<?php

namespace App\Strategy;

use App\Util\Guess;
use App\Util\Wordle;

class StrategyDecider
{
    private LetterReductionStrategy $letterReductionStrategy;
    private FrequencyStrategy $frequencyStrategy;
    private BestGuessStrategy $bestGuessStrategy;
    private OpeningStrategy $openingStrategy;

    public function __construct(
        LetterReductionStrategy $letterReductionStrategy,
        FrequencyStrategy $frequencyStrategy,
        BestGuessStrategy $bestGuessStrategy,
        OpeningStrategy $openingStrategy)
    {
        $this->letterReductionStrategy = $letterReductionStrategy;
        $this->frequencyStrategy = $frequencyStrategy;
        $this->bestGuessStrategy = $bestGuessStrategy;
        $this->openingStrategy = $openingStrategy;
    }

    public function getStrategyResults(Wordle $wordle): array
    {
        $results = [];

        try {
            $results[] = $this->openingStrategy->getResults($wordle);
        } catch (NoGuessException$e) {
        }

        try {
            $results[] = $this->bestGuessStrategy->getResults($wordle);
        } catch (NoGuessException$e) {
        }

        try {
            $results[] = $this->letterReductionStrategy->getResults($wordle);
        } catch (NoGuessException$e) {
        }

        try {
            $results[] = $this->frequencyStrategy->getResults($wordle);
        } catch (NoGuessException $e) {
        }

        return $results;
    }

    public function getBestGuess(Wordle $wordle): Guess
    {
        $results = $this->getStrategyResults($wordle);

        return $results[0]->getGuess();
    }

    public static function getPrimaryStrategy(Wordle $wordle, Database $database): Strategy
    {
        $stats = $wordle->getStats();
        $numGuesses = $stats->getResultCount();

        if ($numGuesses < 2) {
            return new StartingStrategy($numGuesses);
        }

        $numUnknownLetters = count($stats->getUnknownLetters());

        if ($numGuesses < 4 && $numUnknownLetters > 17) {
            return new LetterReductionStrategy($database);
        }

        return new IndexUsageStrategy($database);
    }
}
