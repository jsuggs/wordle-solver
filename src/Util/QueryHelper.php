<?php

namespace App\Util;

class QueryHelper
{
    public static function getExclusionQuery(Stats $stats): string
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

                if (count($excludedLetters)) {
                    $wrongLetterList = self::letterList($excludedLetters);
                    $sql .= sprintf(' AND c%d NOT IN (%s)', $idx, $wrongLetterList);
                }
            }
        }

        // Make sure that the word uses letters that are in the wrong place
        foreach ($stats->getWrongLocationLetters() as $index => $letters) {
            $potentialLocations = array_diff(Wordle::$indexes, [$index], array_keys($stats->getCorrectLetters()));
            foreach ($letters as $letter) {
                $alternateindexeSql = implode(' OR ', array_map(function ($index) use ($letter) {
                    return sprintf("c%d = '%s'", $index, $letter);
                }, $potentialLocations));

                $sql .= sprintf(' AND (%s)', $alternateindexeSql);
            }
        }

        return $sql;
    }

    public static function letterList(array $letters): string
    {
        return implode(',', array_map(function ($letter) {
            return sprintf("'%s'", $letter);
        }, $letters));
    }
}
