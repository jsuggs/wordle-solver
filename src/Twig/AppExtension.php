<?php

namespace App\Twig;

use App\Util\Wordle;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('wordle_url_params', [$this, 'getWordleParams']),
        ];
    }

    public function getWordleParams(Wordle $wordle, int $idx): array
    {
        $result = $wordle->atIdx($idx);

        return [
            'idx' => $idx,
            'results' => json_encode($result->getResultsData()),
        ];
    }
}
