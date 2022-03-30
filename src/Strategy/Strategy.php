<?php

namespace App\Strategy;

use App\Util\Wordle;

abstract class Strategy
{
    abstract public function getResults(Wordle $wordle): StrategyResults;

    abstract public function getName(): string;

    abstract public function getDescription(): string;
}
