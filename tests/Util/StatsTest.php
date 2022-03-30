<?php

namespace App\Tests\Util;

use App\Util\Stats;
use App\Util\Wordle;
use PHPUnit\Framework\TestCase;

class StatsTest extends TestCase
{
    public function testEmptyStats(): void
    {
        $stats = new Stats();
        $this->assertEquals([], $stats->getData());
        $this->assertEquals([], $stats->getCorrectLetters());
        $this->assertEquals([], $stats->getWrongLocationLetters());
        $this->assertEquals([], $stats->getExcludedLetters());
        $this->assertEquals(Wordle::$letters, $stats->getUnknownLetters());
    }

    public function testAddNotFoundLetter(): void
    {
        $stats = new Stats();
        $stats->addNotFoundLetter(1, 'A');

        $data = [
            'NOT_FOUND_LETTERS' => [
                'LETTERS' => ['A' => 1],
                'INDEX' => [1 => 'A'],
            ],
        ];

        $this->assertEquals($data, $stats->getData());
    }

    public function testCorrectLetterLogic(): void
    {
        $stats = new Stats();
        $stats->addCorrectLetter(1, 'A');

        $data = $stats->getData();
        $this->assertEquals('A', $stats->getCorrectLetterForIndex(1));
        $this->assertNull($stats->getCorrectLetterForIndex(2));

        $unknownLetters = Wordle::$letters;
        unset($unknownLetters[0]); // Remove 'A'

        $this->assertEquals($unknownLetters, $stats->getUnknownLetters());

        $stats->addCorrectLetter(3, 'B');
        $stats->addCorrectLetter(5, 'C');

        $data = [
            'CORRECT_LETTERS' => [
                'A' => 1,
                'B' => 3,
                'C' => 5,
            ],
        ];

        $this->assertEquals($data, $stats->getData());
        $this->assertEquals('A', $stats->getCorrectLetterForIndex(1));
        $this->assertEquals('B', $stats->getCorrectLetterForIndex(3));
        $this->assertEquals('C', $stats->getCorrectLetterForIndex(5));
    }

    public function testUnknownIndexes(): void
    {
        $stats = new Stats();
        $this->assertEquals([1, 2, 3, 4, 5], $stats->getUnknownIndexes());

        $stats->addCorrectLetter(3, 'A');
        $this->assertEquals([1, 2, 4, 5], $stats->getUnknownIndexes());

        $stats->addWrongLocationLetter(5, 'B');
        $this->assertEquals([1, 2, 4], $stats->getUnknownIndexes());
    }
}
