<?php

require_once dirname(__FILE__).'/Database.php';

// Blow away the database
$dbFile = Database::DB_FILE;
unlink($dbFile);

$db = new Database();
$db->setupSchema();

// Parse the words file
$wordsFile = 'tmp/words.csv';
$wordsInput = fopen('data/words.csv', 'r');
$wordsOutput = fopen($wordsFile, 'w');

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

$importCommands = <<<SH
sqlite3 $dbFile <<END_SQL
.mode csv
.import data/frequency.csv frequency
.import tmp/words.csv words
END_SQL

SH;

echo $importCommands;

var_dump(shell_exec($importCommands));