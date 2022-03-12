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
	private static $positions = [1,2,3,4,5];

	public function getQuery(Wordle $wordle, Strategy $strategy) : string
	{
		// Let's go with a brute force approach first.
		$sql = 'SELECT word FROM words WHERE 1 == 1 ';

		$notFoundLetters = $this->getNotFoundLetters($wordle);
		$correctLetters = $this->getCorrectLetters($wordle);
		$wrongLocationLetters = $this->getWrongLocationLetters($wordle);
		//var_dump($notFoundLetters, $correctLetters, $wrongLocationLetters);
		$expandedLetterList = self::letterList($notFoundLetters);

		// Build out the inclusion and exclusions based on the results we have made so far
		foreach (self::$positions as $position) {
			// If the letter is correct, use it
			if (isset($correctLetters[$position])) {
				$sql .= sprintf(" AND c%d = '%s'", $position, $correctLetters[$position]);
			} else {
				// Always exclude the letters that aren't in the word all together
				// Contionally exclude the words with letters that aren't in the right place
				$excludedPositionalLetters = (isset($wrongLocationLetters[$position]))
					? array_unique(array_merge($notFoundLetters, $wrongLocationLetters[$position]))
					: $notFoundLetters;

				$wrongLetterList = self::letterList($excludedPositionalLetters);
				$sql .= sprintf(' AND c%d NOT IN (%s)', $position, $wrongLetterList);
			}
		}

		// Make sure that the word has letters that are in the wrong place
		// Note: I think double letters is going to have to be refactored here
		foreach ($wrongLocationLetters as $wrongPosition => $letters) {
			$potentialLocations = array_diff(self::$positions, [$wrongPosition]);

			$alternatePositionSql = implode(' OR ', array_map(function($position) use ($letters) {
				// TODO: This is wrong, only using one of the letters
				return sprintf("c%d = '%s'", $position, $letters[0]);
			}, $potentialLocations));

			$sql .= sprintf('AND (%s)', $alternatePositionSql);
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

	private function getWrongLocationLetters(Wordle $wordle) : array
	{
		$wrongLocationLetters = [];

		foreach ($wordle->results as $result) {
			if ($result->c1 == Result::WRONG_LOCATION) {
				$wrongLocationLetters[1][] = $result->word{0};
			}
			if ($result->c2 == Result::WRONG_LOCATION) {
				$wrongLocationLetters[2][] = $result->word{1};
			}
			if ($result->c3 == Result::WRONG_LOCATION) {
				$wrongLocationLetters[3][] = $result->word{2};
			}
			if ($result->c4 == Result::WRONG_LOCATION) {
				$wrongLocationLetters[4][] = $result->word{3};
			}
			if ($result->c5 == Result::WRONG_LOCATION) {
				$wrongLocationLetters[5][] = $result->word{4};
			}
		}

		return $wrongLocationLetters;
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

	private static function letterList(array $letters)
	{
		return implode(',', array_map(function($letter) {
			return sprintf("'%s'", $letter);
		}, $letters));
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