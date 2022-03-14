<?php

class Wordle
{
	public static $indexes = [1,2,3,4,5];
	public static $letters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
	private string $cacheKey;
	private array $results = [];

	public function addResult(Result $result)
	{
		$this->results[] = $result;
	}

	public function setResults(array $results)
	{
		$this->results = $results;
	}

	public function getStats() : Stats
	{
		// Can cache the stats, so not rebuilt each time
		return $this->buildStats();
	}

	private function buildStats() : Stats
	{
		$stats = new Stats();
		$resultCount = 0;
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
			$resultCount++;
		}
		$stats->setResultCount($resultCount);
		
		return $stats;
	}
}

class Stats
{
	private array $data;
	private int $resultCount;

	public function addNotFoundLetter(int $idx, string $letter)
	{
		$this->data['NOT_FOUND_LETTERS']['LETTERS'][$letter] = ($this->data['NOT_FOUND_LETTERS']['LETTERS'][$letter] ?? 0) + 1;
		$this->data['NOT_FOUND_LETTERS']['INDEX'][$idx] = $letter;
	}

	public function getExcludedLetters() : array
	{
		return array_keys($this->data['NOT_FOUND_LETTERS']['LETTERS'] ?? []);
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

	public function getUnknownLetters() : array
	{
		return array_diff(
			Wordle::$letters,
			array_keys($this->data['NOT_FOUND_LETTERS']['LETTERS'] ?? []),
			array_keys($this->data['CORRECT_LETTERS']['LETTERS'] ?? [])
		);
	}

	public function getUnknownPositions()
	{
		$numCorrectLetters = count($this->getCorrectLetters());

		return 5 - $numCorrectLetters;
	}

	public function setResultCount(int $resultCount)
	{
		$this->resultCount = $resultCount;
	}

	public function getResultCount()
	{
		return $this->resultCount;
	}
}

class Result
{
	public const CORRECT = 'C';
	public const NOT_FOUND = 'N';
	public const WRONG_LOCATION = 'W';

	public $c1, $c2, $c3, $c4, $c5;
	public $word;

	public static function fromMask(string $word, string $mask) : Result
	{
		$instance = new self();
		$instance->setWord($word);

		foreach (Wordle::$indexes as $idx) {
			$instance->{sprintf('c%d', $idx)} = $mask{$idx - 1};
		}

		return $instance;
	}

	public function setWord(string $word)
	{
		$this->word = $word;
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
	abstract public function getName(): string;
}

class FallBackStrategy extends Strategy
{
	public function guess(Wordle $wordle) : Guess
	{
		// TODO
	}

	public function getName() : string
	{
		return 'FallBack';
	}
}

abstract class DatabaseStrategy extends Strategy
{
	protected Database $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	public function guess(Wordle $wordle) : Guess
	{
		$query = $this->getQuery($wordle->getStats());
		// This may change to allow for more than one result to come back, along w metadata
		$word = $this->database->executeWordQuery($query);

		if (!$word) {
			throw new Exception('No Guess');
		}

		$guess = new Guess($word);

		return $guess;
	}

	protected function checkIfWordExist(Stats $stats) : string
	{
		//
		$sql = 'SELECT w.word FROM words w WHERE 1 == 1 ';
		$sql .= QueryBuilder::getExclusionQuery($stats);

		return $this->database->executeWordQuery($sql);
	}

	//abstract protected function getQuery(Stats $stats) : string;
}

class FrequencyStrategy extends DatabaseStrategy
{
	protected function getQuery(Stats $stats) : string
	{
		// Let's go with a brute force approach first.
		$sql = 'SELECT w.word FROM words w INNER JOIN frequency f ON w.word = f.word WHERE 1 == 1 ';
		$sql .= QueryBuilder::getExclusionQuery($stats);
		$sql .= ' ORDER BY f.frequency LIMIT 1';

		return $sql;
	}

	public function getName() : string
	{
		return 'Frequency';
	}
}

class LetterReductionStrategy extends DatabaseStrategy
{
	// The goal of this strategy is to eliminate letters
	public function guess(Wordle $wordle) : Guess
	{
		$stats = $wordle->getStats();
		$knownLetters = $stats->getCorrectLetters();
		$exclusionQuery = QueryBuilder::getExclusionQuery($stats);

		$sql = 'SELECT w.word FROM words w WHERE 1 == 1 ';
		$sql .= QueryBuilder::getExclusionQuery($stats);
		$numLettersToTry = 5 - count($knownLetters['INDEXES'] ?? []);
		$unknownIndexes = array_diff(Wordle::$indexes, array_keys($knownLetters));
		//var_dump('xxx', $knownLetters, $unknownIndexes, $numLettersToTry);
		while ($numLettersToTry > 1) {
			// TODO - Build this function to get the unique set of columns to exclude
			$indexCombos = [
				['left' => 1, 'right' => 2],
				['left' => 1, 'right' => 3],
				['left' => 1, 'right' => 4],
			];

			$notMatchingEachOther = ' AND ' . implode(' AND ', array_map(function($combo) {
				return sprintf(" c%d != c%d ", $combo['left'], $combo['right']);
			}, $indexCombos));

			// If there are known letters, don't use those
			$notMatchingKnown = '';
			if (count($knownLetters)) {
				$notMatchingKnown = ' AND ' . implode(' AND ', array_map(function($idx) use ($knownLetters) {
					return sprintf(" c%d NOT IN (%s) ", $idx, QueryBuilder::letterList(array_keys($knownLetters['LETTERS'])));
				}, $unknownIndexes));
			}

			$loopSql = $sql .= $notMatchingEachOther . $notMatchingKnown;
			//var_dump($loopSql);

			$word = $this->database->executeWordQuery($loopSql);

			if ($word) {
				return new Guess($this, $word);
			}
			//var_dump($word);
			//die();

			// This is the start of the hack of the stats.
			// Put in some new data, see what comes back.
			$numLettersToTry--;
		}
		//die('xxx');

		$word = $this->database->executeWordQuery($sql);

		if (!$word) {
			throw new Exception('No Guess');
		}

		$guess = new Guess($this, $word);

		return $guess;
	}

	public function getName() : string
	{
		return 'Letter Reduction';
	}
}

class IndexUsageStrategy extends DatabaseStrategy
{
	// The goal of this strategy is to eliminate letters
	public function guess(Wordle $wordle) : Guess
	{
		$stats = $wordle->getStats();
		$knownLetters = $stats->getCorrectLetters();
		$exclusionQuery = QueryBuilder::getExclusionQuery($stats);

		$distributions = [];
		foreach (Wordle::$indexes as $idx) {
			if (!array_key_exists($idx, $knownLetters)) {
				$distributions[$idx] = $this->getLetterDistribution($idx, [], $exclusionQuery);
			}
		}
		$countByIndex = $this->getCountByIndex($distributions);

		// Start off with a more simple algorithm.  Just pick the single letter with highest number of words.
		// Can expand this logic later.  A hack for now.
		$mostPoularIndex = $this->getMostPopularIndex($countByIndex);

		$newStats = clone $stats;
		$newStats->addCorrectLetter($mostPoularIndex['idx'], $mostPoularIndex['letter']);

		// Get the positions that are NOT known and the letters that are NOT known
		$sql = 'SELECT w.word FROM words w WHERE 1 == 1 ';
		$sql .= QueryBuilder::getExclusionQuery($newStats);

		$word = $this->database->executeWordQuery($sql);

		if (!$word) {
			throw new Exception('No Guess');
		}

		$guess = new Guess($this, $word);

		return $guess;
	}

	protected function getQuery(Stats $stats) : string
	{
		return 'TODO: think about class structure';
	}

	protected function getLetterDistribution(int $column, array $known, string $exclusionQuery)
	{
		$sql = sprintf("SELECT c%d AS letter, COUNT(*) AS num_words FROM words WHERE 1=1 %s", $column, $exclusionQuery);
		$sql .= sprintf(" GROUP BY c%d ORDER BY num_words DESC", $column);

		$data = $this->database->executeQuery($sql);

		return $data;
	}

	protected function getCountByIndex(array $distributions) : array
	{
		$data = [];

		foreach ($distributions as $idx => $distributionList) {
			$max = 0;
			foreach ($distributionList as $distribution) {
				$data[$idx][$distribution['letter']] = $distribution['num_words'];
				/*
				var_dump($distribution);
				if ($distribution['num_words'] > $max) {
					$max = $distribution['num_words'];
					$topDistributions[$idx] = $distribution['letter'];
				}*/
			}
		}

		return $data;
	}

	protected function getMostPopularIndex(array $countByIndex)
	{
		$max = 0;
		$result = [];

		foreach ($countByIndex as $idx => $list) {
			// php hack for getting key/value of first element in the list
			$popularity = reset($list);
			$letter = array_key_first($list);
			if ($popularity > $max) {
				$max = $popularity;
				$result = [
					'idx' => $idx,
					'letter' => $letter,
				];
			}
		}

		return $result;
	}

	public function getName() : string
	{
		return 'Reduction';
	}
}

class StartingStrategy extends Strategy
{
	private int $idx;

	public function __construct(int $idx)
	{
		$this->idx = $idx;
	}

	public function guess(Wordle $wordle) : Guess
	{
		switch($this->idx) {
			case 0: return new Guess($this, 'STONE');
			case 1: return new Guess($this, 'GRAIL');
			default: return new Guess($this, 'CHUMP');
		}
	}

	public function getName() : string
	{
		return 'Starting';
	}
}

class StrategyDecider
{
	public static function getPrimaryStrategy(Wordle $wordle, Database $database) : Strategy
	{
		$stats = $wordle->getStats();
		$numGuesses = $stats->getResultCount();

		if ($numGuesses < 2)  {
			return new StartingStrategy($numGuesses);
		}

		$numUnknownLetters = count($stats->getUnknownLetters());

		if ($numGuesses < 4 && $numUnknownLetters > 17) {
			return new LetterReductionStrategy($database);
		}

		return new IndexUsageStrategy($database);
	}
}

class QueryBuilder
{
	public static function getExclusionQuery(Stats $stats) : string
	{
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
		//var_dump($sql)

		return $sql;
	}

	public static function letterList(array $letters)
	{
		return implode(',', array_map(function($letter) {
			return sprintf("'%s'", $letter);
		}, $letters));
	}
}

class InputMapper
{
	public function mapJson(string $data) : Wordle
	{
		$json = json_decode($data, true);

		$wordle = new Wordle();
		foreach ($json['results'] as $data) {
			$wordle->results[] = Result::fromMask($data['word'], $data['result']);
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