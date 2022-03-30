<?php

namespace App\Util;

class Stats
{
    private array $data = [];
    private int $resultCount;

    public function getData(): array
    {
        return $this->data;
    }

    public function addNotFoundLetter(int $idx, string $letter)
    {
        $this->data['NOT_FOUND_LETTERS']['LETTERS'][$letter] = ($this->data['NOT_FOUND_LETTERS']['LETTERS'][$letter] ?? 0) + 1;
        $this->data['NOT_FOUND_LETTERS']['INDEX'][$idx] = $letter;
    }

    public function getExcludedLetters(): array
    {
        return array_keys($this->data['NOT_FOUND_LETTERS']['LETTERS'] ?? []);
    }

    public function addCorrectLetter(int $idx, string $letter)
    {
        $this->data['CORRECT_LETTERS'][$letter] = $idx;
    }

    public function getCorrectLetters(): array
    {
        return $this->data['CORRECT_LETTERS'] ?? [];
    }

    public function getCorrectLetterForIndex(int $idx): ?string
    {
        return false === array_search($idx, $this->data['CORRECT_LETTERS'] ?? [])
            ? null
            : array_search($idx, $this->data['CORRECT_LETTERS']);
    }

    public function addWrongLocationLetter(int $idx, string $letter)
    {
        $this->data['WRONG_LOCATION']['INDEX'][$idx][] = $letter;
        $this->data['WRONG_LOCATION']['LETTERS'][$letter][] = $idx;
    }

    public function getWrongLocationLettersForIndex(int $idx): array
    {
        return $this->data['WRONG_LOCATION']['INDEX'][$idx] ?? [];
    }

    public function getWrongLocationLetters(): array
    {
        return $this->data['WRONG_LOCATION']['INDEX'] ?? [];
    }

    public function getUnknownLetters(): array
    {
        return array_diff(
            Wordle::$letters,
            array_keys($this->data['NOT_FOUND_LETTERS']['LETTERS'] ?? []),
            array_keys($this->data['CORRECT_LETTERS'] ?? [])
        );
    }

    public function getUnknownPositions()
    {
        $numCorrectLetters = count($this->getCorrectLetters());

        return 5 - $numCorrectLetters;
    }

    public function getUnknownIndexes(): array
    {
        $indexes = [1, 2, 3, 4, 5];

        foreach ($this->data['CORRECT_LETTERS'] ?? [] as $letter => $idx) {
            unset($indexes[array_search($idx, $indexes)]);
        }

        foreach ($this->data['WRONG_LOCATION']['INDEX'] ?? [] as $idx => $letters) {
            unset($indexes[array_search($idx, $indexes)]);
        }

        return array_values($indexes);
    }

    public function setResultCount(int $resultCount)
    {
        $this->resultCount = $resultCount;
    }

    public function getResultCount()
    {
        return $this->resultCount;
    }
}
