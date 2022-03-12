<?php
require_once dirname(__FILE__).'/Database.php';
require_once dirname(__FILE__).'/Wordle.php';

$dbFile = 'words.db';

$db = new Database($dbFile);
$mapper = new InputMapper();
$wordle = $mapper->mapFile($argv[1]);
$solver = new Solver($wordle, $db);
$result = $solver->solve();

echo sprintf("Guess: %s\n", $result->word);