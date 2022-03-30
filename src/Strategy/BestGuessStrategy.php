<?php

namespace App\Strategy;

use App\Service\LetterDistributionService;
use App\Service\StatsService;
use App\Util\Guess;
use App\Util\QueryHelper;
use App\Util\Wordle;
use Doctrine\DBAL\Connection;

class BestGuessStrategy extends DatabaseStrategy
{
    private const LETTER_SCORE_TABLE = <<<SQL
CREATE TEMPORARY TABLE letter_score (
    letter char(1)
  , total INT
  , v1 INT
  , v2 INT
  , v3 INT
  , v4 INT
  , v5 INT
)
SQL;
    private LetterDistributionService $letterDistributionService;

    public function __construct(Connection $conn, LetterDistributionService $letterDistributionService)
    {
        parent::__construct($conn);
        $this->letterDistributionService = $letterDistributionService;
    }

    public function getResults(Wordle $wordle): StrategyResults
    {
        $stats = StatsService::getStats($wordle);
        $unknownIndexes = $stats->getUnknownIndexes();
        $exclusionQuery = QueryHelper::getExclusionQuery($stats);

        $letterStats = $this->letterDistributionService->getLetterDistribution($wordle);

        // Create a table for the stats
        $this->conn->executeQuery(self::LETTER_SCORE_TABLE);
        foreach (Wordle::$letters as $letter) {
            $letterInsert[] = sprintf('("%s", %d , %d, %d, %d, %d, %d)',
                $letter,
                $letterStats['total'][$letter] ?? 0,
                $letterStats['indexes'][1][$letter] ?? 0,
                $letterStats['indexes'][2][$letter] ?? 0,
                $letterStats['indexes'][3][$letter] ?? 0,
                $letterStats['indexes'][4][$letter] ?? 0,
                $letterStats['indexes'][5][$letter] ?? 0
            );
        }
        $letterScoreSql = "INSERT INTO letter_score (letter, total, v1, v2, v3, v4, v5) VALUES\n";
        $letterScoreSql .= implode(",\n", $letterInsert);
        $this->conn->executeQuery($letterScoreSql);

        if (count($unknownIndexes)) {
            $valueSql = implode(' + ', array_map(function ($idx) {
                return sprintf('s%d.v%d', $idx, $idx);
            }, $unknownIndexes));
        } else {
            $valueSql = '0';
        }

        $sql = 'SELECT word, '.$valueSql.' score FROM words w ';
        foreach (Wordle::$indexes as $idx) {
            $joins[] = sprintf('INNER JOIN letter_score s%d on c%d = s%d.letter', $idx, $idx, $idx);
            // $sql .= ' s1.total as total_score FROM words w INNER JOIN letter_score s1 ON c1 = s1.letter';
        }
        $sql .= implode(' ', $joins);
        $sql .= ' WHERE 1 == 1 '.$exclusionQuery;
        $sql .= ' ORDER BY score DESC LIMIT 5';
        // dump($sql, $stats, $valueSql);
        // die();

        $results = $this->conn->fetchAllAssociative($sql);

        $this->conn->executeQuery('DROP TABLE letter_score');

        if (count($results)) {
            $guess = new Guess($results[0]['word'], $results[0]);

            return new StrategyResults($this, $guess, $results);
        }

        throw new NoGuessException('No Guess');
    }

    public function getName(): string
    {
        return 'Best Guess';
    }

    public function getDescription(): string
    {
        return 'The best!';
    }
}
