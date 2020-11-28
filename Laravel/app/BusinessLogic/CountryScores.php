<?php

namespace App\BusinessLogic;

use App\Models\Country;
use App\Models\MissingDataHandler;

class MultiDatasetStuff {
    protected $countryCodes;
    $this->countryCodes = array_map(function($value){
        return $value['alpha_three_code'];
    },Country::select('alpha_three_code')->where('alpha_three_code','!=',null)->get()->toArray());
    $this->countryCount = count($this->countryCodes);

}


class DefaultCalculation {
    protected $calculator;
    public function __construct($dataType, $parentContext){
        if ($dataType==='numeric') $this->calculator = new NumericCalculation($parentContext);
    }
    public function getResults(){
        return $this->calculator.get();
    }
}


class NumericCalculation {
    protected $returnObject;
    protected $context;
    public function __construct($parentContext){
        $this->context = $parentContext;
    }
    public function get(){
        $this->returnObject = array_merge($this->calcCountriesWithData(), $this->calcCountriesWithoutData());
        return $this->returnObject;
    }
    protected function calcCountriesWithData(){
        $nonNormalizedScores =null;
        foreach ($this->context->countriesWithData as $country=>$value){
            $nonNormalizedScores[$country] = $this->scoreCalculate($value);
        }
        $normalizedScores = null;
        if ($this->context->normalizationPercentage >0){
            $normalizedScores =  $this->normalizeScores($nonNormalizedScores);
        } else {
            $normalizedScores = $nonNormalizedScores;
        }
        $weightedAndNormalizedScores = null;
        foreach ($normalizedScores as $country => $score){
            $weightedAndNormalizedScores[$country] = $score * $this->context->weight;
        }
        return $weightedAndNormalizedScores;
    }
    protected function scoreCalculate($actualValue){
        $max = $this->context->maxNonNormalizedScore;
        $onePercent = ($this->context->datasetMagnitude*1.0)/$max;
        $percentSimilarity = $max-(abs($actualValue - $this->idealValue)*1.0)/$onePercent;
        return $percentSimilarity;
    }
    protected function normalizeScores($existingScores){
        arsort($existingScores);//now first item should have highest possible score, and last item worst
        $totalCountries = count($existingScores);
        $rank = $totalCountries;
        $interpolatedScores = [];//interpolation algorithm is used for normalization here
        foreach ($existingScores as $country=>$nnScore){//nn = non Normalized
            $normalizedScore = ($this->context->maxNonNormalizedScore/$totalCountries*1.0)*$rank;
            $interpolatedScore = 
                $normalizedScore + 
                ($this->context->normalizationPercentage-100.0)*
                (($nnScore-$normalizedScore)/(0.0-100.0));
            $interpolatedScores[$country] = $interpolatedScore;
            $rank --;
        }
        return $interpolatedScores;
    }
    protected function calcCountriesWithoutData(){
        $missingDataHandler = new MissingDataHandler();
        $getScoreParams = [
            'existingScores' => $this->returnObject,
            'method' => $this->context->missingDataHandlerMethod,
            'inputValue' =>$this->context->missingDataHandlerInputValue,
            'dataType' =>'numeric',
        ];
        $missingDataScore = (new MissingDataHandler())->getScore($getScoreParams);
        return $this->getCountriesWithoutDataScored($missingDataScore);
    }
    protected function getCountriesWithoutDataScored($score){
        return array_map(function($v)use ($score) {return $score;},$this->context->countriesWithoutData);
    }
}



class SingleDatasetScores {
    //internal properties used by this class
    protected $originalDataset;
    protected $missingDataHandlerMethod;
    protected $missingDataHandlerInput;
    protected $datasetType;
    protected $idealValue;
    protected $customScoreFunction;

    protected $finalScores;
    protected $finalDataWasMissing;
    protected $countryCount;
    protected $countriesWithData;
    protected $countriesWithoutData;
    protected $dataMagnitude;
    protected $datasetWeight;
    protected $initScores;
    protected $normalazationPercentage;
    protected $normalizedScores;
    protected $maxInitScore;
    protected $weight;
    protected function separateMetadataFromCountryData($dataset, $countryCodes){
        //1st object in return array is metada, second is the rest
                $testingFunction = function($val, $key) use ($countryCodes){
                    return (in_array($key,$countryCodes))?false:true;
                };
                return arrayFilterGetBoth($dataset, $testingFunction);
    }
            
    //constructor function
    function __construct(
        array $originalDataset,
        string $missingDataHandlerMethod,
        ?float $missingDataHandlerInput,
        int $weight,
        $idealValue,
        $customScoreFunction,
        $normalizationPercentage
        ){
        $this->originalDataset = $originalDataset;
        $this->missingDataHandlerInput = $missingDataHandlerInput;
        $this->missingDataHandlerMethod = $missingDataHandlerMethod;
        $this->weight = $weight;
        $this->datasetType = $originalDataset['data_type'];
        $this->idealValue = $idealValue;
        $this->customScoreFunction = $customScoreFunction;
        $this->dataMagnitude = abs($originalDataset['max_value']-$originalDataset['min_value']);
        $this->datasetWeight = $weight;
        $this->normalizationPercentage = $normalizationPercentage;
        $this->maxInitScore = 100; // represents precentage
       
        [$metadata, $countriesData] = $this->separateMetadataFromCountryData($originalDataset, $countryCodes);
        [$countriesOriginallyWithData, $countriesOriginallyWithoutData] = arrayFilterGetBoth($countriesData, function($val,$key){
            return $val !== null;
        });
        $this->countriesWithData = clone($countriesOriginallyWithData);
        $this->countriesWithoutData = clone($countriesOriginallyWithoutData);

        $shouldCalculateMissingDataScoresFirst = ($missingDataHandlerMethod == 'specificValue' && $missingDataHandlerInput);
        if ($shouldCalculateMissingDataScoresFirst){
            foreach ($this->countriesWithoutData as $country => $noData){
                $this->countriesWithData[$country] = $missingDataHandlerInput;
            }
            //now empty the countriesWithoutData array
            $this->countriesWithoutData = [];
        }

        $shouldDoCustomCalculation = $idealValue ===null && !empty($customScoreFunction);
        if ($shouldDoCustomCalculation){$this->finalScores = ['errors'=>'Sorry, custom score calculations are not yet supported'];} 
        else{
            $defaultCalculator = new DefaultCalculation('numeric');
            $result = $defaultCalculator.getResults();
        }

    }
   
    //primary return function
    public function getScoresObject(){
        //compile the data into the final structure
        //calculate the ranks and percentiles for each country
        //at the same time        
        arsort($this->finalScores);
        $currentRank = 1;
        $returnObject = [];
        foreach ($this->finalScores as $country =>$score){
            $percentile = 100-(($currentRank/($this->countryCount+1.0))*100.0);
            $returnObject[$country] = [
                'score'=> $score,
                'rank'=> $currentRank,
                'percentile'=> $percentile,
                'dataWasMissing'=>$this->finalDataWasMissing[$country]
            ];
            $currentRank++;
        }
        return $returnObject;
    }
    public function getRanksObject(){

    }
    
    //utility function used by both /scores POST and /scores GET
    public function calculateScores($inputDatasets){
        
        $responseObject = [];//see above the function for description of this object
        //populate the response object with each country and their names
        $countries = Country::select('alpha_three_code','primary_name')->where('alpha_three_code','!=',null)->get();
        foreach ($countries as $country){
            $country= $country['alpha_three_code'];
            $responseObject[$country]= [
                'primary_name'=>$country['primary_name'],
                'totalScore'=>0,
                'rank'=>0,
                'percentile'=>0,
                'categoryBreakdown'=>[],
                'scoreBreakdown'=>[]
            ];

        }

        //!CALCULATION, for each dataset
        foreach ($inputDatasets as $dataset){
            //get all the variables required for the calculation
            $id = $dataset['id'];
            $weight = $dataset['weight'];
            // if the weight is zero exit current dataset immediately without doing anything
            if ($weight == 0) continue;
            $idealValue = $dataset['idealValue'];
            $customScoreFunction = $dataset['customScoreFunction'];
            $missingDataHandlerMethod = $dataset['missingDataHandlerMethod'];
            $missingDataHandlerInput = array_key_exists('missingDataHandlerInput',$dataset)?$dataset['missingDataHandlerInput']:null;
            $normalizationPercentage = $dataset['normalizationPercentage'];
            //get the scores for this dataset
            $scoreCalculator = new DatasetScores(
                Dataset::where([['id','=',$dataset['id']],['id','!=',null]])->get()[0]->toArray(),
                $missingDataHandlerMethod,
                $missingDataHandlerInput,
                $weight,
                $idealValue,
                $customScoreFunction,
                $normalizationPercentage,
            );
            $scores = $scoreCalculator->getScoresObject();

            //push the scores for this dataset
            // Log::info($scores);
            foreach ($scores as $country=>$score){
                //set this data to the per-dataset score breakdown
                $responseObject[$country]['scoreBreakdown'][$id] = $score; //sets the 'score', 'rank', 'percentage', et.c all at once
                //update the category breakdowns
                $currentCategoryScore = false;
                if (isset($responseObject[$country]['categoryBreakdown'][$dataset['category']])){

                $currentCategoryScore = $responseObject[$country]['categoryBreakdown'][$dataset['category']];
                }
                if ($currentCategoryScore){
                    //its initialized, just add the new score to it
                    $responseObject[$country]['categoryBreakdown'][$dataset['category']] = $currentCategoryScore + $score['score'];
                }else {
                    //initialize it with this score
                    $responseObject[$country]['categoryBreakdown'][$dataset['category']] = $score['score'];
                }
                //add this data to the overall total score for each country
                // Log::info($score);
                $responseObject[$country]['totalScore'] += $score['score'];
            }
        }

        //get relative rankings for each country now that they all have final scores
        //first get a list with just the total scores, and country codes as keys
        $rankingArray ;
        foreach($countries as $country){
            $country = $country['alpha_three_code'];
            $rankingArray[$country] = $responseObject[$country]['totalScore'];
        }
        //sort that new list, highest scores are first on the list now
        arsort($rankingArray);
        $currentRank = 1;
        foreach($rankingArray as $country => $score){
        $responseObject[$country]['rank'] = $currentRank;
        //get the percentile also
        $percentile = 100-(($currentRank/(count($rankingArray)+1.0))*100.0);
        $responseObject[$country]['percentile'] = $percentile;
        $currentRank ++;
        }
        return $responseObject;
    }
    public function getMissingDataHandlerMethods(Request $request){
        $missingDataHandler = new MissingDataHandler();
        $methodsList = $missingDataHandler->objectWithAll();
        return response()->json($methodsList,200);
    }
}

$countries = Country::select('alpha_three_code','primary_name')->where('alpha_three_code','!=',null)->get();

class ScoresResponseObject {
    protected $scoresInputObject;
    protected $scoresByCountryCode;
    protected $scoresByCountryAndCategory;
    protected $scoresByCountryAndDataset;
    protected $ranksByCountryCode;
    protected $ranksByCountryAndDataset;
    protected $percentilesByCountryCode;
    protected $percentilesByCountryAndDataset;

    public function __construct($scoresInputObject){
        $this->scoresInputObject = $scoresInputObject;
        $this->calculateScores();
        $this->calculateRanks();
        $this->calculatePercentiles();
    }
    protected function calculateScores (){
        $scoreCalculator = new ScoreCalculator(context);
        $this->scoresByCountryCode = $scoreCalculator->getCountryCodesWithScores();
        $this->scoresByCountryAndCategory = $scoreCalculator->getCountryCodesWithCategoryBreakdowns();
        $this->scoresByCountryAndDataset = $scoreCalculator->getCountryCodesWithDatasetBreakdowns();
    }
    protected function calculateRanks(){
        $ranksCalculator = new RankCalculator($this->scoresByCountryCode);
        $this->ranksByCountryCode = $ranksCalculator->getCountryCodesWithRanks();
        $this->ranksByCountryAndDataset = $ranksCalculator->getCountryCodesWithDatasetBreakdowns();
    }
    protected function calculatePercentiles(){
        $percentilesCalculator = new PercentileCalculator($this->scoresByCountryCode);
        $this->percentilesByCountryCode = $percentilesCalculator->getCountryCodesWithPercentiles();
        $this->percentilesByCountryCode = $percentilesCalculator->getCountryCodesWithDatasetBreakdowns();
    }
    public function get(){
        $responseObject = [];
        foreach ($this->getResponseObjectFields('top') as $country){
            foreach($this->getResponseObjectFields('second') as $field){
                $responseObject[$country][$field] = $this->getCountryData($field,$country);
            }
        }
        return $responseObject;
    }
    protected function getResponseObjectFields ($level){
        if ($level === 'top') return [];//TODO all country alpha_three_codes
        if ($level ==='second') return ['primary_name','totalScore','rank','percentile','categoryBreakdown','scoreBreakdown'];
        if ($level === 'categoryBreakdown')return [];//Todo all category names
        if ($level === 'scoreBreakdownTop') return [];//TODO all datasetIDs with scores
        if ($level === 'scoreBreakdownBottom') return ['score','rank','percentile','dataWasMissing'];
    }
    protected function getCountryData($fieldName, $countryCode){
        return $this->dataByCountryCode[$countryCode][$fieldName];
    }
    protected $dataByCountryCode;

    public __construct(){
        $this->dataByCountryCode = (new DataByCountryCode())->get();
    }
    
}


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