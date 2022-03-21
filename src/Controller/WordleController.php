<?php

namespace App\Controller;

use App\Service\FrequentWordsService;
use App\Service\LetterDistributionService;
use App\Service\Solver;
use App\Strategy\StrategyDecider;
use App\Util\Result;
use App\Util\Wordle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WordleController extends AbstractController
{
    /**
     * @Route("/", name="wordle")
     */
    public function index(Request $request, LetterDistributionService $letterDistributionService, FrequentWordsService $frequentWordsService, StrategyDecider $strategyDecider): Response
    {
        $wordle = $this->getWordle($request);

        $letterDistribution = $letterDistributionService->getLetterDistribution($wordle);
        $strategyResults = $strategyDecider->getStrategyResults($wordle);

        return $this->render('wordle/index.html.twig', [
            'wordle' => $wordle,
            'letterDistribution' => $letterDistribution,
            'frequentWords' => $frequentWordsService->getFrequentWords($wordle),
            'strategyResults' => $strategyResults,
        ]);
    }

    /**
     * @Route("/solve", name="solve")
     */
    public function solve(Request $request, Solver $solver): Response
    {
        $word = $request->query->get('word');
        $wordle = $this->getWordle($request);

        $wordle = $solver->solve($word, $wordle);

        return $this->redirectToRoute('wordle', [
            'results' => json_encode($wordle->getResultsData()),
        ]);
    }

    private function getWordle(Request $request): Wordle
    {
        $wordle = new Wordle();

        if ($request->query->has('results')) {
            if ($results = json_decode($request->query->get('results'), true)) {
                foreach ($results as $data) {
                    $wordle->addResult(Result::fromMask($data['word'], $data['mask']));
                }
            }
        }

        if ($request->query->has('word') && $request->query->has('mask')) {
            $wordle->addResult(Result::fromMask($request->query->get('word'), $request->query->get('mask')));
        }

        return $wordle;
    }
}
