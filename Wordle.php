<?php

class Wordle
{
	public array $results = [];
	public static $indexes = [1,2,3,4,5];

	public function getStats() : array
	{
		$stats = [];
		$notFoundLetters = [];
		foreach ($this->results as $result) {
			foreach (self::$indexes as $idx) {
				$wordIdx = $idx - 1;
				$resultProp = sprintf('c%d', $idx);
				$resultValue = $result->{$resultProp};
				$letter = $result->word{$wordIdx};
				if ($resultValue == Result::NOT_FOUND) {
					$stats['NOT_FOUND_LETTERS']['LETTERS'][$letter] = ($stats['NOT_FOUND_LETTERS']['LETTERS'][$letter] ?? 0) + 1;
					$stats['NOT_FOUND_LETTERS']['INDEX'][$idx] = $letter;
				} elseif ($resultValue == Result::CORRECT) {
					$stats['CORRECT_LETTERS'][$idx] = $letter;
					$stats['CORRECT_LETTERS']['LETTERS'][$letter] = 1;
					$stats['CORRECT_LETTERS']['INDEXES'][$idx] = 1;
				} elseif ($resultValue == Result::WRONG_LOCATION) {
					$stats['WRONG_LOCATION']['INDEX'][$idx][] = $letter;
					$stats['WRONG_LOCATION']['LETTERS'][$letter][] = $idx;
				}
			}
		}
		
		return $stats;
	}

	public function getGuesses() {
		return array_map(function($result) {
			return $result->word;
		}, $this->results);
	}
}

class Result
{
	public const CORRECT = 'C';
	public const NOT_FOUND = 'N';
	public const WRONG_LOCATION = 'W';

	public $c1, $c2, $c3, $c4, $c5;
	public $word;

	public static function fromMask(string $mask) : Result
	{
		$instance = new self();

		foreach (Wordle::$indexes as $idx) {
			$instance->{sprintf('c%d', $idx)} = $mask{$idx - 1};
		}

		return $instance;
	}

	public function __toString()
	{
		return $this->c1 . $this->c2 . $this->c3 . $this->c4 . $this->c5;
	}

	public function isCorrect() : bool
	{
		return 'CCCCC' === strval($this);
	}
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
	private Database $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	public function solve(Wordle $wordle) : Guess
	{
		$primaryStrategy = StrategyDecider::getPrimaryStrategy($wordle, $this->database);

		$guess = $primaryStrategy->guess($wordle);

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
		if (count($wordle->getGuesses()) == 0)  {
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
	public function getQuery(Wordle $wordle, string $fieldName) : string
	{
		// Let's go with a brute force approach first.
		$sql = 'SELECT word FROM words WHERE 1 == 1 ';

		$stats = $wordle->getStats();
		//var_dump($stats);

		// Build out the inclusion and exclusions based on the results we have made so far
		foreach (Wordle::$indexes as $idx) {
			// If the letter is correct, use it
			if (isset($stats['CORRECT_LETTERS'][$idx])) {
				$sql .= sprintf(" AND c%d = '%s'", $idx, $stats['CORRECT_LETTERS'][$idx]);
			} else {
				// Always exclude the letters that aren't in the word all together
				$excludedLetters = array_keys($stats['NOT_FOUND_LETTERS']['LETTERS']);
				// Contionally exclude the words with letters that aren't in the right place
				if (isset($stats['WRONG_LOCATION']['INDEX'][$idx])) {
					$excludedLetters = array_merge($excludedLetters, $stats['WRONG_LOCATION']['INDEX'][$idx]);
				}

				$wrongLetterList = self::letterList($excludedLetters);
				$sql .= sprintf(' AND c%d NOT IN (%s)', $idx, $wrongLetterList);
			}
		}

		// Make sure that the word uses letters that are in the wrong place
		foreach ($stats['WRONG_LOCATION']['INDEX'] as $index => $letters) {
			$potentialLocations = array_diff(Wordle::$indexes, [$index], array_keys($stats['CORRECT_LETTERS']['INDEXES'] ?? []));
			foreach ($letters as $letter) {
				$alternateindexeSql = implode(' OR ', array_map(function($index) use ($letter) {
					return sprintf("c%d = '%s'", $index, $letter);
				}, $potentialLocations));

				$sql .= sprintf(' AND (%s)', $alternateindexeSql);
			}
		}

		$sql .= sprintf(' ORDER BY %s LIMIT 1', $fieldName);

		var_dump($sql);

		return $sql;
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

		$data = file_get_contents($filename);

		return $this->mapJson($data);
		
	}

	public function mapJson(string $data) : Wordle
	{
		$json = json_decode($data, true);

		$wordle = new Wordle();
		foreach ($json['results'] as $data) {
			$result = Result::fromMask($data['result']);
			$result->word = $data['word'];
			$wordle->results[] = $result;
		}

		return $wordle;
	}
}

class ResultTester
{
	public static function getGuessResult(string $word, string $guess) : Result
	{
		$result = new Result();
		$result->word = $guess;

		foreach (Wordle::$indexes as $idx) {
			$resultProp = sprintf('c%d', $idx);
			$value = Result::NOT_FOUND;

			if ($word{$idx - 1} === $guess{$idx - 1}) {
				// In the correct spot
				$value = Result::CORRECT;
			} else {
				// Determine if the number is in the wrong spot, but account for duplicate letters
				$numLetterInGuess = substr_count($guess, $guess{$idx - 1});
				$numLettersInWord = substr_count($word, $guess{$idx - 1});

				// So if the letter is in the word, but not too many times
				if ($numLettersInWord > 0 && $numLetterInGuess >= $numLettersInWord) {
					$value = Result::WRONG_LOCATION;
				}
				//var_dump($word, $guess, $idx, $guess{$idx - 1}, $numLettersInWord, $numLetterInGuess);
			}

			$result->$resultProp = $value;
		}

		return $result;
	}
}