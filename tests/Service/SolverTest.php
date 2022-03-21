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

        if (!$wordle->isSolved()) {
            dump($wordle, $word);
            exit();
        }

        $this->assertTrue($wordle->isSolved());
    }

    public function getWords(): array
    {
        $wordFile = __DIR__.'/../../var/data/words.csv';

        return array_map(function ($line) {
            $word = strtoupper(rtrim($line));

            return [$word];
        }, file($wordFile));
    }
}
