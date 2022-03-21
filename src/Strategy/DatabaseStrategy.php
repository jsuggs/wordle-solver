<?php

namespace App\Strategy;

use App\Util\Guess;
use App\Util\Wordle;
use Doctrine\DBAL\Connection;

abstract class DatabaseStrategy extends Strategy
{
    protected Connection $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function guess(Wordle $wordle): Guess
    {
        $query = $this->getQuery($wordle->getStats());
        // This may change to allow for more than one result to come back, along w metadata
        $word = $this->database->executeWordQuery($query);

        if (!$word) {
            throw new Exception('No Guess');
        }

        $guess = new Guess($word);

        return $guess;
    }

    protected function checkIfWordExist(Stats $stats): string
    {
        $sql = 'SELECT w.word FROM words w WHERE 1 == 1 ';
        $sql .= QueryBuilder::getExclusionQuery($stats);

        return $this->database->executeWordQuery($sql);
    }

    // abstract protected function getQuery(Stats $stats) : string;
}
