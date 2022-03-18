<?php

namespace App\Tests\Util;

use App\Util\ResultTester;
use PHPUnit\Framework\TestCase;

class ResultTesterTest extends TestCase
{
    /** @dataProvider getResults */
    public function testResultTester(string $word, string $guess, string $expected): void
    {
        $this->assertEquals($expected, ResultTester::getGuessResult($word, $guess));
    }

    public function getResults() : array
    {
        return [
            ['PAINT', 'STONE', 'NWNCN'],
            ['PAPER', 'PAPPY', 'CCCWN'],
            ['PAPER', 'EVERY', 'WNWWN'],
            ['PAINT', 'PAPER', 'CCWNN'],
            ['HUMPH', 'WHICH', 'NWNNC'],
            ['SISSY', 'SHIPS', 'CNWNW'],
        ];
    }
}
