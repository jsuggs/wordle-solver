<?php

namespace App\Strategy;

use App\Util\Guess;
use App\Util\Wordle;

class StrategyResults
{
	private Guess $guess;
	private array $results;
	private array $steps = [];

	public function __construct(Guess $guess, array $results)
	{
		$this->guess = $guess;
		$this->results = $results;
	}
	
	public function getGuess() : Guess
	{
		return $this->guess;
	}

	public function getResults() : array
	{
		return $this->results;
	}

	public function addStep(string $description)
	{
		$this->steps[] = $description;
	}
}