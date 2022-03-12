<?php

class Wordle
{
	public array $guesses = [];
	public array $results = [];
}

class Result
{
	public const CORRECT = 'C';
	public const NOT_FOUND = 'N';
	public const WRONG_LOCATION = 'W';

	public $c1, $c2, $c3, $c4, $c5;
}

class Guess
{
	public string $word;

	public function __construct(string $word)
	{
		$this->word = $word;
	}
}

class Solver
{
	private Wordle $wordle;

	public function __construct(Wordle $wordle)
	{
		$this->wordle = $wordle;
	}

	public function solve() : Guess
	{
		// Add logic here

		$guess = new Guess('GRAIL');

		return $guess;
	}
}

class InputMapper
{
	public function mapFile(string $filename) : Wordle
	{
		if (!file_exists($filename)) {
			throw new Exception(sprintf('File %s does not exist', $filename));
		}

		$json = json_decode(file_get_contents($filename), true);

		$wordle = new Wordle();
		$wordle->guesses = $json['guesses'];
		foreach ($json['results'] as $data) {
			$result = new Result();
			$result->c1 = $data['c1'];
			$result->c2 = $data['c2'];
			$result->c3 = $data['c3'];
			$result->c4 = $data['c4'];
			$result->c5 = $data['c5'];	
			$wordle->results[] = $result;
		}

		var_dump($json);
		var_dump($wordle);

		return $wordle;
	}
}