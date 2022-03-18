<?php

namespace App\Strategy;

use App\Service\StatsService;
use App\Util\Guess;
use App\Util\QueryHelper;
use App\Util\Wordle;

class FrequencyStrategy extends DatabaseStrategy
{
	public function getResults(Wordle $wordle) : StrategyResults
	{
		$stats = StatsService::getStats($wordle);

		// Let's go with a brute force approach first.
		$sql = 'SELECT w.word, IFNULL(f.frequency, 10000) AS frequency FROM words w LEFT OUTER JOIN frequency f ON w.word = f.word WHERE 1 == 1 ';
		$sql .= QueryHelper::getExclusionQuery($stats);
		$sql .= ' ORDER BY frequency LIMIT 1';

		$results = $this->conn->fetchAllAssociative($sql);
			
		$guess = new Guess($this, $results[0]['word']);

		return new StrategyResults($guess, $results);
	}

	public function getName() : string
	{
		return 'Frequency';
	}
}