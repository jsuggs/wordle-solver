<?php

namespace App\Tests\Util;

use App\Util\Stats;
use PHPUnit\Framework\TestCase;

class StatsTest extends TestCase
{
    public function testEmptyStats(): void
    {
        $stats = new Stats();
        $this->assertEquals([], $stats->getData());
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

    public function testCorrectLetter(): void
    {
        $stats = new Stats();
        $stats->addCorrectLetter(1, 'A');
        $stats->addCorrectLetter(3, 'B');
        $stats->addCorrectLetter(5, 'C');

        $data = [
            'CORRECT_LETTERS' => [
                'LETTERS' => ['A' => 1, 'B' => 1, 'C' => 1],
                'INDEXES' => [1 => 1, 3 => 1, 5 => 1],
                '1' => 'A',
                '3' => 'B',
                '5' => 'C',
            ],
        ];


        $this->assertEquals($data, $stats->getData());
        $this->assertEquals('A', $stats->getCorrectLetterForIndex(1));
    }
}
