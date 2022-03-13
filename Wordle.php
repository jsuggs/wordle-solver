<?php

class Wordle
{
	public array $results = [];
	public static $indexes = [1,2,3,4,5];
	private string $cacheKey;

	public function getStats() : Stats
	{
		// Can cache the stats, so not rebuilt each time
		return $this->buildStats();
	}

	private function buildStats() : Stats
	{
		$stats = new Stats();
		foreach ($this->results as $result) {
			foreach (self::$indexes as $idx) {
				$resultValue = $result->{sprintf('c%d', $idx)};
				$letter = $result->word{$idx - 1};
				if ($resultValue == Result::NOT_FOUND) {
					$stats->addNotFoundLetter($idx, $letter);
				} elseif ($resultValue == Result::CORRECT) {
					$stats->addCorrectLetter($idx, $letter);
				} elseif ($resultValue == Result::WRONG_LOCATION) {
					$stats->addWrongLocationLetter($idx, $letter);
				}
			}
		}
		
		return $stats;
	}

	public function getGuesses() : array
	{
		return array_map(function($result) {
			return $result->word;
		}, $this->results);
	}
}

class Stats
{
	private array $data;

	public function addNotFoundLetter(int $idx, string $letter)
	{
		$this->data['NOT_FOUND_LETTERS']['LETTERS'][$letter] = ($stats['NOT_FOUND_LETTERS']['LETTERS'][$letter] ?? 0) + 1;
		$this->data['NOT_FOUND_LETTERS']['INDEX'][$idx] = $letter;
	}

	public function getExcludedLetters() : array
	{
		return array_keys($this->data['NOT_FOUND_LETTERS']['LETTERS']);
	}

	public function addCorrectLetter(int $idx, string $letter)
	{
		$this->data['CORRECT_LETTERS'][$idx] = $letter;
		$this->data['CORRECT_LETTERS']['LETTERS'][$letter] = 1;
		$this->data['CORRECT_LETTERS']['INDEXES'][$idx] = 1;
	}

	public function getCorrectLetters() : array
	{
		return $this->data['CORRECT_LETTERS'] ?? [];
	}

	public function getCorrectLetterForIndex(int $idx) : ?string
	{
		return $this->data['CORRECT_LETTERS'][$idx] ?? null;
	}

	public function addWrongLocationLetter(int $idx, string $letter)
	{
		$this->data['WRONG_LOCATION']['INDEX'][$idx][] = $letter;
		$this->data['WRONG_LOCATION']['LETTERS'][$letter][] = $idx;
	}

	public function getWrongLocationLettersForIndex(int $idx) : array
	{
		return $this->data['WRONG_LOCATION']['INDEX'][$idx] ?? [];
	}

	public function getWrongLocationLetters() : array
	{
		return $stats['WRONG_LOCATION']['INDEX'] ?? [];
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
		$query = $this->getQuery($wordle);
		// This may change to allow for more than one result to come back, along w metadata
		$word = $this->database->exeuteWordQuery($query);

		if (!$word) {
			throw new Exception('No Guess');
		}

		$guess = new Guess($word);

		return $guess;
	}

	abstract protected function getQuery(Wordle $wordle) : string;
}

class FrequencyStrategy extends DatabaseStrategy
{
	protected function getQuery(Wordle $wordle) : string
	{
		// Let's go with a brute force approach first.
		$sql = 'SELECT w.word FROM words w INNER JOIN frequency f ON w.word = f.word WHERE 1 == 1 ';
		$sql .= QueryBuilder::getStandardQuery($wordle);
		$sql .= ' ORDER BY f.frequency LIMIT 1';
		var_dump($sql);

		return $sql;
	}
}

class LetterReductionStrategy extends DatabaseStrategy
{
	// The goal of this strategy is to eliminate letters
	protected function getQuery(Wordle $wordle) : string
	{
		$stats = $wordle->getStats();

		// Get the positions that are NOT known and the letters that are NOT known
		$sql = 'SELECT w.word FROM words w WHERE 1 == 1 ';
		$sql .= QueryBuilder::getStandardQuery($wordle);
		var_dump($sql);

		return $sql;
	}

	protected function getLetterDistribution(int $column, array $known)
	{
		$sql = sprintf("SELECT c%d, COUNT(*) AS num_words FROM words %s");
		$sql .= sprintf(" GROUP BY c%d ORDER BY num_words DESC", $column);

		return $sql;
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
		$numGuesses = count($wordle->getGuesses());

		// Basic logic for determining which strategy to use.
		if ($numGuesses === 0)  {
			return new StartingStrategy;
		}

		$stats = $wordle->getStats();

		/*if ($numGuesses < 3 && $stats->getUnknownLetters() > 10) {
			return new LetterReductionStrategy($database);
		}*/

		return new FrequencyStrategy($database);
	}

	public static function getFallbackStrategy(Wordle $wordle) : Strategy
	{
		return new FallBackStrategy($wordle);
	}
}

class QueryBuilder
{
	public static function getStandardQuery(Wordle $wordle) : string
	{
		$stats = $wordle->getStats();
		//var_dump($stats);
		$sql = '';

		// Build out the inclusion and exclusions based on the results we have made so far
		foreach (Wordle::$indexes as $idx) {
			// If the letter is correct, use it
			if ($correctLetter = $stats->getCorrectLetterForIndex($idx)) {
				$sql .= sprintf(" AND c%d = '%s'", $idx, $correctLetter);
			} else {
				// Always exclude the letters that aren't in the word all together
				$excludedLetters = $stats->getExcludedLetters();

				// Contionally exclude the words with letters that aren't in the right place
				$wrongLettersForIndex = $stats->getWrongLocationLettersForIndex($idx);
				if (count($wrongLettersForIndex)) {
					$excludedLetters = array_merge($excludedLetters, $wrongLettersForIndex);
				}

				$wrongLetterList = self::letterList($excludedLetters);
				$sql .= sprintf(' AND c%d NOT IN (%s)', $idx, $wrongLetterList);
			}
		}

		// Make sure that the word uses letters that are in the wrong place
		foreach ($stats->getWrongLocationLetters() as $index => $letters) {
			$potentialLocations = array_diff(Wordle::$indexes, [$index], array_keys($stats->getCorrectLetters()));
			foreach ($letters as $letter) {
				$alternateindexeSql = implode(' OR ', array_map(function($index) use ($letter) {
					return sprintf("c%d = '%s'", $index, $letter);
				}, $potentialLocations));

				$sql .= sprintf(' AND (%s)', $alternateindexeSql);
			}
		}

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
				if ($numLettersInWord > 0 ) {
					$value = Result::WRONG_LOCATION;
				}
				//var_dump($word, $guess, $idx, $guess{$idx - 1}, $numLettersInWord, $numLetterInGuess);
			}

			$result->$resultProp = $value;
		}

		return $result;
	}
}