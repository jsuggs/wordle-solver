<?php

namespace App\Strategy;

use App\Service\StatsService;
use App\Util\Guess;
use App\Util\QueryHelper;
use App\Util\Wordle;

class LetterReductionStrategy extends DatabaseStrategy
{
    public function getResults(Wordle $wordle): StrategyResults
    {
        $stats = StatsService::getStats($wordle);
        $knownLetters = $stats->getCorrectLetters();
        $exclusionQuery = QueryHelper::getExclusionQuery($stats);

        $sql = 'SELECT w.word, IFNULL(f.frequency, 10000) AS frequency FROM words w LEFT OUTER JOIN frequency f ON w.word = f.word WHERE 1 == 1 ';
        $sql .= $exclusionQuery;

        $numLettersToTry = 5 - count($knownLetters);
        $unknownIndexes = array_diff(Wordle::$indexes, array_keys($knownLetters));

        while ($numLettersToTry > 1) {
            // TODO - make sure we only use the nummLetterToTry
            $indexesToTry = $unknownIndexes;
            while ($left = array_pop($indexesToTry)) {
                foreach ($indexesToTry as $right) {
                    $indexCombos[] = [
                        'left' => $left,
                        'right' => $right,
                    ];
                }
            }

            $notMatchingEachOther = ' AND '.implode(' AND ', array_map(function ($combo) {
                return sprintf(' c%d != c%d ', $combo['left'], $combo['right']);
            }, $indexCombos));

            // If there are known letters, don't use those
            $notMatchingKnown = '';
            if (count($knownLetters)) {
                $notMatchingKnown = ' AND '.implode(' AND ', array_map(function ($idx) use ($knownLetters) {
                    return sprintf(' c%d NOT IN (%s) ', $idx, QueryHelper::letterList(array_keys($knownLetters)));
                }, $unknownIndexes));
            }

            $loopSql = $sql.$notMatchingEachOther.$notMatchingKnown.' ORDER BY frequency ASC LIMIT 50';
            // dump($sql, $knownLetters, $loopSql);

            $results = $this->conn->fetchAllAssociative($loopSql);

            if (count($results)) {
                $guess = new Guess($results[0]['word']);

                return new StrategyResults($this, $guess, $results);
            }

            --$numLettersToTry;
        }

        throw new NoGuessException('No Guess');
    }

    public function getName(): string
    {
        return 'Letter Reduction';
    }

    public function getDescription(): string
    {
        return 'This goal of this strategy is to reduce the number of letters that are unknown.  As a result, it is only useful in middle guesses as it doesn\'t account for words with double letters';
    }
}
