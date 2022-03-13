<?php

class Database 
{
	private const WORDS = <<<SQL
CREATE TABLE words (
	word CHARACTER(5) PRIMARY KEY,
	c1 CHARACTER(1),
	c2 CHARACTER(1),
	c3 CHARACTER(1),
	c4 CHARACTER(1),
	c5 CHARACTER(1)
);
SQL;

private const FREQUENCY =<<<SQL
CREATE TABLE frequency (
	word CHARACTER(5) PRIMARY KEY,
	frequency UNSIGNED TINY INT
);
SQL;
	public const DB_FILE = 'words.db';

	private $conn;

	public function __construct()
	{
		$this->conn = new SQLITE3(self::DB_FILE);
	}

	public function setupSchema()
	{
		$this->conn->exec(self::WORDS);
		$this->conn->exec(self::FREQUENCY);
	}

	public function getDatabaseFilePath() : string
	{
		return self::DB_FILE;
	}

	public function exeuteWordQuery(string $query) : ?string
	{
		$query = $this->conn->query($query);

		$data = $query->fetchArray();

		return $data
			? $data['word']
			: null;
	}

	public function importFileIntoTable(string $table, string $filename)
	{
		$sql = sprintf('.import %s %s;', $filename, $table);

		$this->conn->exec($sql);
	}

	private function getConnection()
	{
		return $this->conn;
	}
}