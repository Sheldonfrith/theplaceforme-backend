<?php
namespace App\BusinessLogic\CountryScores;


use App\Models\MissingDataHandler;
use Illuminate\Support\Facades\Log;

class SingleDatasetCalculator
{
    protected $dc; //datasetContext
    protected $countryContext;
    protected $sc; //scoresInputContext
    protected $missingDataHandlerMethod;
    protected $missingDataHandlerInput;
    protected $countriesWithData;
    protected $countriesWithoutData;
    public function __construct($datasetID, $countryContext, $scoresInputContext)
    {
        $this->dc = new DatasetContext($datasetID, $countryContext);
        $this->countriesWithData = $this->dc->getCountriesWithData();
        $this->countriesWithoutData = $this->dc->getCountriesWithoutData();
        $this->countryContext = $countryContext;
        $this->sc = $scoresInputContext->getDatasetByID($datasetID);
        $shouldCalculateMissingDataScoresFirst = ($this->sc['missingDataHandlerMethod'] == 'specificValue' && $this->sc['missingDataHandlerInput']);
        if ($shouldCalculateMissingDataScoresFirst) $this->calculateMissingDataScoresFirst();
        //$shouldDoCustomCalculation = $this->sc->idealValue ===null && !empty($customScoreFunction); NOT IMPLEMENTED YET
        //determine type of calcualtion here based on dataset type, currently only 'numeric' datasets supported
        $this->calculateAll();
    }
    protected function calculateMissingDataScoresFirst()
    {
        foreach ($this->countriesWithoutData as $country => $noData) {
            $this->countriesWithData[$country] = $this->sc->missingDataHandlerInput;
        }
        $this->countriesWithoutData = [];
    }

    public function getScoresByCountryCode()
    {
        return $this->finalScoresByCountryCode;
    }
    protected function calculateAll()
    {
        $countriesWithData = $this->calcCountriesWithData();
        $countriesWithoutData = $this->calcCountriesWithoutData($countriesWithData);
        $this->finalScoresByCountryCode = array_merge($countriesWithData, $countriesWithoutData);
    }
    protected function calcCountriesWithData()
    {
        $nonNormalizedScores = null;
        foreach ($this->dc->getCountriesWithData() as $country => $value) {
            $nonNormalizedScores[$country] = $this->scoreCalculate($value);
        }
        $normalizedScores = null;
        if ($this->sc['normalizationPercentage'] > 0) {
            $normalizedScores =  $this->normalizeScores($nonNormalizedScores);
        } else {
            $normalizedScores = $nonNormalizedScores;
        }
        $weightedAndNormalizedScores = null;
        foreach ($normalizedScores as $country => $score) {
            $weightedAndNormalizedScores[$country] = $score * $this->sc['weight'];
        }
        return $weightedAndNormalizedScores;
    }
    protected function scoreCalculate($actualValue)
    {
        $max = config('constants.scores.max_non_normalized_score');
        $onePercent = ($this->dc->getDatasetMagnitude() * 1.0) / $max;
        $percentSimilarity = $max - (abs($actualValue - $this->sc['idealValue']) * 1.0) / $onePercent;
        return $percentSimilarity;
    }
    protected function normalizeScores($existingScores)
    {
        arsort($existingScores); //now first item should have highest possible score, and last item worst
        $totalCountries = count($existingScores);
        $rank = $totalCountries;
        $interpolatedScores = []; //interpolation algorithm is used for normalization here
        foreach ($existingScores as $country => $nnScore) { //nn = non Normalized
            $normalizedScore = (config('constants.scores.max_non_normalized_score') / $totalCountries * 1.0) * $rank;
            $interpolatedScore =
                $normalizedScore +
                ($this->sc['normalizationPercentage'] - 100.0) *
                (($nnScore - $normalizedScore) / (0.0 - 100.0));
            $interpolatedScores[$country] = $interpolatedScore;
            $rank--;
        }
        return $interpolatedScores;
    }
    protected function calcCountriesWithoutData($countriesWithDataScores)
    {
        $missingDataHandler = new MissingDataHandler();
        $getScoreParams = [
            'existingScores' => $countriesWithDataScores,
            'method' => $this->sc['missingDataHandlerMethod'],
            'inputValue' => $this->sc['missingDataHandlerInput'],
            'dataType' => 'numeric',
        ];
        $missingDataScore = $missingDataHandler->getScore($getScoreParams);
        return $this->getCountriesWithoutDataScored($missingDataScore);
    }
    protected function getCountriesWithoutDataScored($score)
    {
        return array_map(function ($v) use ($score) {
            return $score;
        }, $this->dc->getCountriesWithoutData());
    }
}
