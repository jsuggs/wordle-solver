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
		$primaryStrategy = StrategyDecider::getPrimaryStrategy($this->wordle, $this->database);

		$guess = $primaryStrategy->guess($this->wordle);

		if (!$guess) {
			// Fallback Strategy?
			throw new Exception('No Guess');
		}

		return $guess;
	}
}

abstract class Strategy
{
	abstract public function guess(Wordle $wordle) : Guess;
}

class FallBackStrategy extends Strategy
{
	public function guess(Wordle $wordle) : Guess
	{
		// TODO
	}
}

abstract class DatabaseStrategy extends Strategy
{
	private Database $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	public function guess(Wordle $wordle) : Guess
	{
		$qb = new QueryBuilder();
		$query = $qb->getQuery($wordle, $this->getFieldName());

		$word = $this->database->exeuteWordQuery($query);

		if (!$word) {
			throw new Exception('No Guess');
		}

		$guess = new Guess($word);

		return $guess;
	}

	abstract protected function getFieldName() : string;
}

class FrequencyStrategy extends DatabaseStrategy
{
	private const FIELD_NAME = 'frequency';

	protected function getFieldName() : string
	{
		return self::FIELD_NAME;
	}
}

class StartingStrategy extends Strategy
{
	public function guess(Wordle $wordle) : Guess
	{
		return new Guess('STONE');
	}
}

class StrategyDecider
{
	public static function getPrimaryStrategy(Wordle $wordle, Database $database) : Strategy
	{
		// Basic logic for determining which strategy to use.
		if (count($wordle->guesses) == 0)  {
			return new StartingStrategy;
		}

		return new FrequencyStrategy($database);
	}

	public static function getFallbackStrategy(Wordle $wordle) : Strategy
	{
		return new FallBackStrategy($wordle);
	}
}

class QueryBuilder
{
	private $c1Clause, $c2Clause;
	private static $positions = [1,2,3,4,5];

	public function getQuery(Wordle $wordle, string $fieldName) : string
	{
		// Let's go with a brute force approach first.
		$sql = 'SELECT word FROM words WHERE 1 == 1 ';

		list($notFoundLetterPositions, $notFoundLetters, $notFoundLetterCount) = $this->getNotFoundLetters($wordle);
		$correctLetters = $this->getCorrectLetters($wordle);
		$wrongLocationLetters = $this->getWrongLocationLetters($wordle);
		//var_dump($notFoundLetters, $notFoundLetterPositions, $wrongLocationLetters);

		// Build out the inclusion and exclusions based on the results we have made so far
		foreach (self::$positions as $position) {
			// If the letter is correct, use it
			if (isset($correctLetters[$position])) {
				$sql .= sprintf(" AND c%d = '%s'", $position, $correctLetters[$position]);
			} else {
				var_dump($position,$notFoundLetterPositions[$position], $wrongLocationLetters[$position]);
				// Always exclude the letters that aren't in the word all together
				// Contionally exclude the words with letters that aren't in the right place
				// Unless there are more than one letter
				// See if we can figure out if a letter is already accounted for
				// Do a count
				// always not found

				$excludedPositionalLetters = $notFoundLetters;
				$excludedPositionalLetters = (isset($wrongLocationLetters[$position]))
					? array_unique(array_merge($notFoundLetters, $notFoundLetterPositions[$position], $wrongLocationLetters[$position]))
					: $notFoundLetters;

				$wrongLetterList = self::letterList($excludedPositionalLetters);
				$sql .= sprintf(' AND c%d NOT IN (%s)', $position, $wrongLetterList);
			}
		}

		// Make sure that the word has letters that are in the wrong place
		// Note: I think double letters is going to have to be refactored here
		foreach ($wrongLocationLetters as $wrongPosition => $letters) {
			foreach ($letters as $letter) {
				$potentialLocations = array_diff(self::$positions, [$wrongPosition]);

				$alternatePositionSql = implode(' OR ', array_map(function($position) use ($letter) {
					// TODO: This is wrong, only using one of the letters
					return sprintf("c%d = '%s'", $position, $letter);
				}, $potentialLocations));

				$sql .= sprintf(' AND (%s)', $alternatePositionSql);
			}
		}

		$sql .= sprintf(' ORDER BY %s LIMIT 1', $fieldName);

		var_dump($sql);
		var_dump($notFoundLetterCount);

		return $sql;
	}

	private function getNotFoundLetters(Wordle $wordle) : array
	{
		$notFoundLetterPositions = $notFoundLetters = $notFoundLetterCount = [];

		foreach ($wordle->results as $result) {
			if ($result->c1 == Result::NOT_FOUND) {
				$notFoundLetterPositions[1][] = $result->word{0};
				$notFoundLetters[] = $result->word{0};
				$notFoundLetterCount[$result->word{0}]++;
			}
			if ($result->c2 == Result::NOT_FOUND) {
				$notFoundLetterPositions[2][] = $result->word{1};
				$notFoundLetters[] = $result->word{1};
				$notFoundLetterCount[$result->word{1}]++;
			}
			if ($result->c3 == Result::NOT_FOUND) {
				$notFoundLetterPositions[3][] = $result->word{2};
				$notFoundLetters[] = $result->word{2};
				$notFoundLetterCount[$result->word{2}]++;
			}
			if ($result->c4 == Result::NOT_FOUND) {
				$notFoundLetterPositions[4][] = $result->word{3};
				$notFoundLetters[] = $result->word{3};
				$notFoundLetterCount[$result->word{3}]++;
			}
			if ($result->c5 == Result::NOT_FOUND) {
				$notFoundLetterPositions[5][] = $result->word{4};
				$notFoundLetters[] = $result->word{4};
				$notFoundLetterCount[$result->word{4}]++;
			}
		}

		return [$notFoundLetterPositions, array_unique($notFoundLetters), $notFoundLetterCount];
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