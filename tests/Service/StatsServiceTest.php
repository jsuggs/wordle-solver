<?php

namespace App\Tests\Service;

use App\Service\StatsService;
use App\Util\Result;
use App\Util\Wordle;
use PHPUnit\Framework\TestCase;

class StatsServiceTest extends TestCase
{
    public function testGetStats(): void
    {
        $wordle = new Wordle();

        $this->assertEquals([], StatsService::getStats($wordle)->getData());

        $result = Result::fromMask('STONE', 'NNWNC');

        $wordle->addResult($result);

        $expected = [
            'NOT_FOUND_LETTERS' => [
                'LETTERS' => [
                    'S' => 1,
                    'T' => 1,
                    'N' => 1,
                ],
                'INDEX' => [
                    1 => 'S',
                    2 => 'T',
                    4 => 'N',
                ],
            ],
            'WRONG_LOCATION' => [
                'INDEX' => [
                    3 => [
                        0 => 'O',
                    ],
                ],
                'LETTERS' => [
                    'O' => [
                        0 => 3,
                    ],
                ],
            ],
            'CORRECT_LETTERS' => [
                'E' => 5,
            ],
        ];
        $this->assertEquals($expected, StatsService::getStats($wordle)->getData());
    }
}
