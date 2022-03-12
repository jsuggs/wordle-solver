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
	private Database $database;

	public function __construct(Wordle $wordle, Database $database)
	{
		$this->wordle = $wordle;
		$this->database = $database;
	}

	public function solve() : Guess
	{
		$strategy = StrategyDecider::getStrategy($this->wordle);
		$qb = new QueryBuilder();
		$query = $qb->getQuery($this->wordle, $strategy);
		var_dump($query);

		$word = $this->database->exeuteWordQuery($query);

		if (!$word) {
			throw new Exception('No Guess');
		}

		$guess = new Guess($word);

		return $guess;
	}
}

class Strategy
{
	public string $fieldName;
}

class StrategyDecider
{
	public static function getStrategy(Wordle $wordle) : Strategy
	{
		// Hard code this to frequency for now
		$strategy = new Strategy();
		$strategy->fieldName = 'frequency';

		return $strategy;
	}
}

class QueryBuilder
{
	private $c1Clause, $c2Clause;

	public function getQuery(Wordle $wordle, Strategy $strategy) : string
	{
		// Let's go with a brute force approach first.
		$sql = 'SELECT word FROM words WHERE 1 == 1 ';

		$notFoundLetters = $this->getNotFoundLetters($wordle);
		$correctLetters = $this->getCorrectLetters($wordle);
		var_dump($notFoundLetters, $correctLetters);
		$expandedLetterList = implode(',', array_map(function($letter) {
			return sprintf("'%s'", $letter);
		}, $notFoundLetters));

		// Build out the inclusion and exclusions based on the results we have made so far
		foreach ([1,2,3,4,5] as $position) {
			if (isset($correctLetters[$position])) {
				$sql .= sprintf(" AND c%d = '%s'", $position, $correctLetters[$position]);
			} else {
				$sql .= sprintf(' AND c%d NOT IN (%s)', $position, $expandedLetterList);
			}
		}
		$sql .= sprintf(' ORDER BY %s LIMIT 1', $strategy->fieldName);

		return $sql;
	}

	private function getNotFoundLetters(Wordle $wordle) : array
	{
		$notFoundLetters = [];
		foreach ($wordle->results as $result) {
			if ($result->c1 == Result::NOT_FOUND) {
				$notFoundLetters[1] = $result->word{0};
			}
			if ($result->c2 == Result::NOT_FOUND) {
				$notFoundLetters[2] = $result->word{1};
			}
			if ($result->c3 == Result::NOT_FOUND) {
				$notFoundLetters[3] = $result->word{2};
			}
			if ($result->c4 == Result::NOT_FOUND) {
				$notFoundLetters[4] = $result->word{3};
			}
			if ($result->c5 == Result::NOT_FOUND) {
				$notFoundLetters[5] = $result->word{4};
			}
		}

		return array_unique($notFoundLetters);
	}

	private function getCorrectLetters(Wordle $wordle) : array
	{
		$correctLetters = [];

		foreach ($wordle->results as $result) {
			if ($result->c1 == Result::CORRECT) {
				$correctLetters[1] = $result->word{0};
			}
			if ($result->c2 == Result::CORRECT) {
				$correctLetters[2] = $result->word{1};
			}
			if ($result->c3 == Result::CORRECT) {
				$correctLetters[3] = $result->word{2};
			}
			if ($result->c4 == Result::CORRECT) {
				$correctLetters[4] = $result->word{3};
			}
			if ($result->c5 == Result::CORRECT) {
				$correctLetters[5] = $result->word{4};
			}
		}

		return $correctLetters;
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
			$result->word = $data['word'];
			$result->c1 = $data['c1'];
			$result->c2 = $data['c2'];
			$result->c3 = $data['c3'];
			$result->c4 = $data['c4'];
			$result->c5 = $data['c5'];	
			$wordle->results[] = $result;
		}

		//var_dump($json);
		var_dump($wordle);

		return $wordle;
	}
}