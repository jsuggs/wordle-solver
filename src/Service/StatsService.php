<?php

namespace App\Service;

use App\Util\Result;
use App\Util\Stats;
use App\Util\Wordle;

class StatsService
{
    public static function getStats(Wordle $wordle): Stats
    {
        $stats = new Stats();
        $resultCount = 0;

        foreach ($wordle->getResults() as $result) {
            foreach (Wordle::$indexes as $idx) {
                $resultValue = $result->getStatus($idx);
                $letter = $result->getLetter($idx);
                if ($resultValue == Result::NOT_FOUND) {
                    $stats->addNotFoundLetter($idx, $letter);
                } elseif ($resultValue == Result::CORRECT) {
                    $stats->addCorrectLetter($idx, $letter);
                } elseif ($resultValue == Result::WRONG_LOCATION) {
                    $stats->addWrongLocationLetter($idx, $letter);
                }
            }
            $resultCount++;
        }
        $stats->setResultCount($resultCount);

        return $stats;
    }
}
