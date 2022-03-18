#!/usr/bin/env php
<?php

$wordsInput = fopen(__DIR__ . '/../var/data/words.csv', 'r');
$wordsOutput = fopen(__DIR__ . '/../var/data/words_expanded.csv', 'w');

while ($word = strtoupper(rtrim(fgets($wordsInput)))) {
	fputcsv($wordsOutput, [
		$word,
		$word{0},
		$word{1},
		$word{2},
		$word{3},
		$word{4},
	]);
}

fclose($wordsInput);
fclose($wordsOutput);