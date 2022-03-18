<?php

namespace App\Service;

use App\Util\QueryHelper;
use App\Util\Wordle;
use Doctrine\DBAL\Connection;

class FrequentWordsService
{
	private Connection $conn;

	public function __construct(Connection $conn)
	{
		$this->conn = $conn;
	}

	public function getFrequentWords(Wordle $wordle, int $maxWords = 50) : array
	{
		$stats = StatsService::getStats($wordle);

		$exclusionQuery = QueryHelper::getExclusionQuery($stats);

		$sql = 'SELECT w.word, f.frequency FROM words w INNER JOIN frequency f ON w.word = f.word WHERE 1 == 1 ';
		$sql .= QueryHelper::getExclusionQuery($stats);
		$sql .= sprintf(' ORDER BY f.frequency LIMIT %d', $maxWords);

		return $this->conn->fetchAllAssociative($sql);
	}
}