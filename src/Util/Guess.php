<?php

namespace App\Util;

use App\Strategy\Strategy;

class Guess
{
	public string $word;
	public Strategy $strategy;

	public function __construct(Strategy $strategy, string $word)
	{
		$this->strategy = $strategy;
		$this->word = $word;
	}

	public function getAlgorithm() : string
	{
		return get_class($this->strategy);
	}
}