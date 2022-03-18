<?php

namespace App\Util;

class Result
{
	public const CORRECT = 'C';
	public const NOT_FOUND = 'N';
	public const WRONG_LOCATION = 'W';

	public $c1, $c2, $c3, $c4, $c5;
	private string $word;

	public function __construct(string $word)
	{
		$this->word = $word;
	}

	public static function fromMask(string $word, string $mask) : Result
	{
		$instance = new self($word);
		$instance->setMask($mask);

		foreach (Wordle::$indexes as $idx) {
			$instance->{sprintf('c%d', $idx)} = $mask[$idx - 1];
		}

		return $instance;
	}

	public function setWord(string $word)
	{
		$this->word = $word;
	}

	public function getWord() : string
	{
		return $this->word;
	}

	public function setMask(string $mask)
	{
		$this->c1 = $mask[0];
		$this->c2 = $mask[1];
		$this->c3 = $mask[2];
		$this->c4 = $mask[3];
		$this->c5 = $mask[4];
	}

	public function getMask() : string
	{
		return strval($this);
	}

	public function getLetter(int $idx) : string
	{
		return $this->word[$idx - 1];
	}

	public function getStatus(int $idx) : string
	{
		return $this->{sprintf('c%d', $idx)};
	}

	public function setStatus(int $idx, string $status)
	{
		$this->{sprintf('c%d', $idx)} = $status;
	}

	public function getStatusName(int $idx) : string
	{
		switch ($this->{sprintf('c%d', $idx)}) {
			case self::CORRECT:
				return 'correct';
			case self::NOT_FOUND:
				return 'not-found';
			case self::WRONG_LOCATION:
				return 'wrong-location';
		}
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