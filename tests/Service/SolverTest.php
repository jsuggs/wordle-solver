<?php

namespace App\Tests\Service;

use App\Service\Solver;
use App\Util\Wordle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SolverTest extends KernelTestCase
{
    /** @dataProvider getWords */
    public function testSolve(string $word): void
    {
        $wordle = new Wordle();

        $container = static::getContainer();
        $solver = $container->get(Solver::class);

        $wordle = $solver->solve($word, $wordle);

        // if (!$wordle->isSolved()) {
        //    $this->assertTrue(true);
        //    exit();
        // }

        $this->assertTrue($wordle->isSolved());
        $this->assertLessThanOrEqual(6, count($wordle->getResults()));
    }

    public function getWords(): array
    {
        $wordFile = __DIR__.'/../../var/data/words.csv';

        return array_slice(array_map(function ($line) {
            $word = strtoupper(rtrim($line));

            return [$word];
        }, file($wordFile)), 0, 500);
    }
}
