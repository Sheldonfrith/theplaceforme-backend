<?php

namespace App\BusinessLogic\CountryScores;

class ScoreCalculator
{

    protected $countryContext;
    protected $scoresInputContext;
    protected $countryScoresTotal;
    protected $countryScoresByCategory;
    protected $countryScoresByDataset;
    protected $countryDataWasMissingByDataset;


    public function __construct($countryContext, $scoresInputContext)
    {
        $this->countryContext = $countryContext;
        $this->scoresInputContext = $scoresInputContext;
        $this->calculate();
    }
    public function getCountryCodesWithScores()
    {
        return $this->countryScoresTotal;
    }
    protected function calculate(): void
    {
        foreach ($this->scoresInputContext->getActiveDatasetIDs() as $datasetID) {
            $thisCategory = $this->scoresInputContext->getDatasetCategoryById();
            $thisDatasetScores = $this->getScoresByCountryCode($datasetID);
            $thisCategoryExistingScores = $this->countryScoresByCategory[$thisCategory];
            $this->countryDataWasMissingByDataset[$datasetID] = (new DatasetContext($datasetID, $this->countryContext))->getCountryNamesWithoutData();
            $this->countryScoresTotal = $this->countryScoresTotal ? array_merge_sum_values($this->countryScoresTotal, $thisDatasetScores) : $thisDatasetScores;
            $this->countryScoresByDataset[$datasetID] = $thisDatasetScores;
            $this->countryScoresByCategory[$thisCategory] = $thisCategoryExistingScores ? array_merge_sum_values($thisDatasetScores, $thisCategoryExistingScores) : $thisDatasetScores;
        }
    }
    //the structure for category breakdown and datasetbreakdown (called score breakdown in the final object)
    //       'categoryBreakdown'=>[
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
    public function getCountryCodesWithCategoryBreakdowns()
    {
        $returnObject = [];
        foreach ($this->countryContext->getAllCountryCodes() as $countryCode) {
            $returnObject[$countryCode] = $this->getAllCategoriesDataForCountry($countryCode);
        }
        return $returnObject;
    }
    public function getCountryCodesWithDatasetBreakdowns()
    {
        //include the full dataset breakdown object here, not just the score part, since it will be easier to calculate
        //with the local context variables
        $returnObject = [];
        foreach ($this->countryContext->getAllCountryCodes() as $countryCode) {
            $returnObject[$countryCode] = $this->getDatasetBreakdowns($countryCode);
        }
    }
    protected function getDatasetBreakdowns($countryCode)
    {
        $returnObject = [];
        $rankCalculator = new RankCalculator();
        $percentileCalculator = new PercentileCalculator();
        foreach ($this->scoresInputContext->getActiveDatasetIDs() as $datasetID) {
            $ranks = $rankCalculator->arrayReplaceValuesWithRanks(true, $this->countryScoresByDataset[$datasetID]);
            $percentiles = $percentileCalculator->arrayReplaceValuesWithPercentiles(true, $this);
            $returnObject[$datasetID] = [
                'score' => $this->countryScoresByDataset[$datasetID][$countryCode],
                'rank' => $ranks[$countryCode],
                'percentile' => $percentiles[$countryCode],
                'dataWasMissing' => $this->countryDataWasMissingByDataset[$datasetID][$countryCode],
            ];
        }
        return $returnObject;
    }

    protected function getAllDatasetsDataForCountry($countryCode)
    {
        $returnObject = [];
        foreach ($this->countryScoresByDataset as $datasetID => $countryScores) {
            $returnObject[$datasetID] = array_filter($countryScores, function ($key) use ($countryCode) {
                return ($key === $countryCode);
            }, ARRAY_FILTER_USE_KEY);
        }
        return $returnObject;
    }

    protected function getAllCategoriesDataForCountry($countryCode)
    {
        $returnObject = [];
        foreach ($this->countryScoresByCategory as $category => $countryScores) {
            $returnObject[$category] = array_filter($countryScores, function ($key) use ($countryCode) {
                return ($key === $countryCode);
            }, ARRAY_FILTER_USE_KEY);
        }
        return $returnObject;
    }
    protected function getAllDatasetScoresByCountryCode()
    {
        foreach ($this->scoresInputContext->getActiveDatasetIDs() as $datasetID) {
            $this->getScoresByCountryCode($datasetID);
        }
    }
    protected function getScoresByCountryCode($datasetID)
    {
        $singleDatasetCalculator = new SingleDatasetCalculator($datasetID, $this->countryContext, $this->scoresInputContext);
        return $singleDatasetCalculator->getScoresByCountryCode();
    }
}
