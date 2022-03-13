<?php

class Wordle
{
	public array $guesses = [];
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
	public function getQuery(Wordle $wordle, string $fieldName) : string
	{
		// Let's go with a brute force approach first.
		$sql = 'SELECT word FROM words WHERE 1 == 1 ';

		$stats = $wordle->getStats();
		var_dump($stats);

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
			$potentialLocations = array_diff(Wordle::$indexes, [$index], array_keys($stats['CORRECT_LETTERS']['INDEXES']));
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

		return $wordle;
	}
}