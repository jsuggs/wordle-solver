<?php

require_once dirname(__FILE__).'/../Wordle.php';

class TestCase
{
	public string $word;
	public string $guess;
	public Result $expectation;

	public function __construct(string $word, string $guess, string $expectation)
	{
		$this->word = $word;
		$this->guess = $guess;
		$this->expectation = Result::fromMask($expectation);
		$this->expectation->word = $guess;
	}
}

$testCases = [
	new TestCase('PAINT', 'STONE', 'NWNCN'),
	new TestCase('PAPER', 'PAPPY', 'CCCWN'),
	new TestCase('PAPER', 'EVERY', 'WNWWN'),
	new TestCase('PAINT', 'PAPER', 'CCWNN'),
	new TestCase('HUMPH', 'WHICH', 'NWNNC'),
	new TestCase('SISSY', 'SHIPS', 'CNWNW'),
];

foreach ($testCases as $testCase) {
	$result = ResultTester::getGuessResult($testCase->word, $testCase->guess);
	if ($result != $testCase->expectation) {
		$error = sprintf('Word: %s, Guess: %s, Expected: %s, Got: %s', $testCase->word, $testCase->guess, $testCase->expectation, $result);
		echo "Error: $error\n";
	}
}