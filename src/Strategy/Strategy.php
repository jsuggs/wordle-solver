<?php

namespace App\Strategy;

use App\Util\Guess;
use App\Util\Wordle;

abstract class Strategy
{
    abstract public function getResults(Wordle $wordle): StrategyResults;
}
