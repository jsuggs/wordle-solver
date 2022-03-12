<?php

class Database 
{
	private const SCHEMA = <<<SQL
CREATE TABLE words (
	word CHARACTER(5) PRIMARY KEY,
	c1 CHARACTER(1),
	c2 CHARACTER(1),
	c3 CHARACTER(1),
	c4 CHARACTER(1),
	c5 CHARACTER(1),
	frequency UNSIGNED TINY INT
)
SQL;

	private $conn;

	public function __construct(string $dbFile)
	{
		$this->conn = new SQLITE3($dbFile);
		//$this->createSchema();

		/*if (!file_exists($dbFile)){
			$this->createSchema();
		}*/
	}

	public function createSchema()
	{
		$this->conn->exec(self::SCHEMA);
	}

	public function exeuteWordQuery(string $query) : ?string
	{
		$query = $this->conn->query($query);

		$data = $query->fetchArray();

		return $data
			? $data['word']
			: null;
	}

	public function importWordsFromFile(string $filename, int $limit = 0)
	{
		if (!file_exists($filename)) {
			throw new Exception(sprintf('File %s does not exist', $filename));
		}

		$insertSQL =<<<SQL
INSERT INTO words (word, c1, c2, c3, c4, c5, frequency) VALUES (:word, :c1, :c2, :c3, :c4, :c5, :frequency)
SQL;
		$stmt = $this->conn->prepare($insertSQL);

		$fh = fopen($filename, 'r');
		$frequency = 0;
		while ($word = fgets($fh)) {
			$stmt->bindValue(':word', strtoupper($word));
			$stmt->bindValue(':c1', strtoupper($word{0}));
			$stmt->bindValue(':c2', strtoupper($word{1}));
			$stmt->bindValue(':c3', strtoupper($word{2}));
			$stmt->bindValue(':c4', strtoupper($word{3}));
			$stmt->bindValue(':c5', strtoupper($word{4}));
			$stmt->bindValue(':frequency', ++$frequency);
			$stmt->execute();

			if ($limit > 0 && $frequency >= $limit) {
				break;
			}
		}
		fclose($fh);
	}

	private function getConnection()
	{
		return $this->conn;
	}
}