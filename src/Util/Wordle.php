<?php

namespace App\Util;

class Wordle
{
    public static $indexes = [1, 2, 3, 4, 5];
    public static $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    private array $results = [];

    public function atIdx(int $idx): Wordle
    {
        $results = $this->results;

        $wordle = new self();
        $wordle->setResults(array_slice($results, 0, ++$idx));

        return $wordle;
    }

    public function addResult(Result $result)
    {
        $this->results[] = $result;
    }

    public function setResults(array $results)
    {
        $this->results = $results;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getResultsData(): array
    {
        $data = [];
        foreach ($this->results as $result) {
            $data[] = [
                'word' => $result->getWord(),
                'mask' => $result->getMask(),
            ];
        }

        return $data;
    }

    public function isSolved(): bool
    {
        $lastResult = end($this->results);

        return $lastResult && $lastResult->isCorrect();
    }
}
