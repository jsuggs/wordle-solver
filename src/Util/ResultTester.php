<?php

namespace App\Util;

class ResultTester
{
    public static function getGuessResult(string $word, string $guess): Result
    {
        $result = new Result($guess);

        foreach (Wordle::$indexes as $idx) {
            $value = Result::NOT_FOUND;

            if ($word{$idx - 1} === $guess[$idx - 1]) {
                // In the correct spot
                $value = Result::CORRECT;
            } else {
                // Determine if the number is in the wrong spot, but account for duplicate letters
                $numLetterInGuess = substr_count($guess, $guess[$idx - 1]);
                $numLettersInWord = substr_count($word, $guess[$idx - 1]);

                // So if the letter is in the word, but not too many times
                if ($numLettersInWord > 0) {
                    $value = Result::WRONG_LOCATION;
                }
                //var_dump($word, $guess, $idx, $guess[$idx - 1], $numLettersInWord, $numLetterInGuess);
            }

            $result->setStatus($idx, $value);
        }

        return $result;
    }
}
