<?php

namespace App\Service;

use App\Util\QueryHelper;
use App\Util\Wordle;
use Doctrine\DBAL\Connection;

class LetterDistributionService
{
    private Connection $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function getLetterDistribution(Wordle $wordle): array
    {
        $stats = StatsService::getStats($wordle);

        $knownLetters = $stats->getCorrectLetters();
        $exclusionQuery = QueryHelper::getExclusionQuery($stats);

        $distributions = [];
        foreach (Wordle::$indexes as $idx) {
            if (!array_key_exists($idx, $knownLetters)) {
                $distributions[$idx] = $this->getLetterDistributionForColumn($idx, $exclusionQuery);
            }
        }
        $total = [];
        foreach ($distributions as $distribution) {
            foreach ($distribution as $letter => $count) {
                $total[$letter] = ($total[$letter] ?? 0) + $count;
            }
        }
        arsort($total);

        return [
            'indexes' => $distributions,
            'total' => $total,
        ];
    }

    protected function getLetterDistributionForColumn(int $column, string $exclusionQuery): array
    {
        $sql = sprintf('SELECT c%d AS letter, COUNT(*) AS num_words FROM words WHERE 1=1 %s', $column, $exclusionQuery);
        $sql .= sprintf(' GROUP BY c%d ORDER BY num_words DESC', $column);

        $data = [];

        foreach ($this->conn->fetchAllAssociative($sql) as $row) {
            $data[$row['letter']] = $row['num_words'];
        }

        return $data;
    }
}
