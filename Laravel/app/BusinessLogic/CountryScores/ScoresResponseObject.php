<?php

namespace App\BusinessLogic\CountryScores;

use Illuminate\Support\Arr;
use App\BusinessLogic\CountryScores\ScoreCalculator;
use App\BusinessLogic\CountryScores\CountryContext;
use App\BusinessLogic\CountryScores\ScoresInputContext;


// example ResponseObject = [
//     'alpha_three_code'=>[
//         'primary_name'=>string,
//         'totalScore'=>int,
//         'rank'=>int,
//         'percentile'=>int,
//         'categoryBreakdown'=>[
//             'cat_name'=>int, //score total
//         ],
//         'scoreBreakdown'=>[[
//             'dataset_id'=>[
//                 'score'
//                 'rank'
//                 'percentile'
//                 'dataWasMissing'
//             ]
//         ]],
//     ]
// ]

class ScoresResponseObject
{
    public function get()
    {
        $responseObject = [];
        foreach ($this->getResponseObjectFields('top') as $country) {
            foreach ($this->getResponseObjectFields('second') as $field) {
                $responseObject[$country][$field] = $this->getCountryData($field, $country);
            }
        }
        return $responseObject;
    }
    public function __construct($scoresInputObject)
    {
        $this->countryContext = new CountryContext();
        $this->scoresInputContext = new ScoresInputContext($scoresInputObject);
        $this->scoresInputObject = $scoresInputObject;
        $this->calculateScores();
        $this->calculateRanks();
        $this->calculatePercentiles();
        $this->allCountryCodes = $this->countryContext->allCountryCodes;
    }

    protected $scoresInputObject;
    protected $scoresByCountryCode;
    protected $ranksByCountryCode;
    protected $ranksByCountryAndDataset;
    protected $percentilesByCountryCode;
    protected $percentilesByCountryAndDataset;
    protected $allCountryCodes;
    protected $scoresInputContext;
    protected $countryContext;
    protected $categoryBreakdownsByCountryCode;
    protected $datasetBreakdownsByCountryCode;
    protected function getCountryData($fieldName, $countryCode)
        {
            if ($fieldName === 'catogeryBreakdown') {
                return $this->categoryBreakdownsByCountryCode[$countryCode];
            }
            if ($fieldName === 'scoreBreakdown') {
                return $this->datasetBreakdownsByCountryCode[$countryCode];
            }
            return $this->dataByCountryCode[$countryCode][$fieldName];
        }
    protected function calculateScores()
    {
        $scoreCalculator = new ScoreCalculator($this->countryContext, $this->scoresInputContext);
        $this->scoresByCountryCode = $scoreCalculator->getCountryCodesWithScores();
        $this->categoryBreakdownsByCountryCode = $scoreCalculator->getCountryCodesWithCategoryBreakdowns();
        $this->datasetBreakdownsByCountryCode = $scoreCalculator->getCountryCodesWithDatasetBreakdowns();
    }
    protected function calculateRanks()
    {
        $ranksCalculator = new RankCalculator($this->scoresByCountryCode);
        $this->ranksByCountryCode = $ranksCalculator->arrayReplaceValuesWithRanks(true, $this->scoresByCountryCode);
    }
    protected function calculatePercentiles()
    {
        $percentilesCalculator = new PercentileCalculator();
        $this->percentilesByCountryCode = $percentilesCalculator->arrayReplaceValuesWithPercentiles(true,$this->scoresByCountryCode);
    }

    protected function getResponseObjectFields($level)
    {
        if ($level === 'top') return $this->allCountryCodes;
        if ($level === 'second') return ['primary_name', 'totalScore', 'rank', 'percentile', 'categoryBreakdown', 'scoreBreakdown'];
        if ($level === 'categoryBreakdown') return $this->getAllActiveCategoryNames();
        if ($level === 'scoreBreakdownTop') return $this->getAllActiveDatasetIDs();
        if ($level === 'scoreBreakdownBottom') return ['score', 'rank', 'percentile', 'dataWasMissing'];
    }

    protected function getAllActiveDatasetIDs()
    {
        $randomCountryCode = $this->allCountryCodes[0];
        $datasetIDsAreKeys = $this->scoresByCountryAndDataset[$randomCountryCode];
        [$datasetIDs, $ignore] = Arr::divide($datasetIDsAreKeys);
        return $datasetIDs;
    }
    protected function getAllActiveCategoryNames()
    {
        $randomCountryCode = $this->allCountryCodes[0];
        $categoriesAreKeys = $this->scoresByCountryAndCategory[$randomCountryCode];
        [$categories, $ignore] = Arr::divide($categoriesAreKeys);
        return $categories;
    }
    
}
