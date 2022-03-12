<?php
require_once dirname(__FILE__).'/Database.php';
require_once dirname(__FILE__).'/Wordle.php';

$dbFile = 'words.db';

$db = new Database($dbFile);
$mapper = new InputMapper();
$wordle = $mapper->mapFile($argv[1]);
$solver = new Solver($wordle);
$result = $solver->solve();

var_dump($result);