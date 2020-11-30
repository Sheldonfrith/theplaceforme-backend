<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;


/**
 * Class for calculating country scores based on questionaire inputs
 * 
 * 
 * 
 */
class ScoreCalculator {
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
    protected $countryCodes;
    protected $initScores;
    protected $normalazationPercentage;
    protected $normalizedScores;
    protected $maxInitScore;
    protected $weight;

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
        $this->countryCodes = array_map(function($value){
            return $value['alpha_three_code'];
        },Country::select('alpha_three_code')->where('alpha_three_code','!=',null)->get()->toArray());
        $this->countryCount = count($this->countryCodes);

        // first separate the meta fields from the actual country data
        $metaFields = array_filter($originalDataset,function($key){
            if (in_array($key,$this->countryCodes)) {return false;}
            return true;
        },ARRAY_FILTER_USE_KEY);
        $countriesData = array_filter($originalDataset,function($key){
            if (in_array($key,$this->countryCodes)) {return true;}
            return false;
        },ARRAY_FILTER_USE_KEY);
        

        //separate the countries dataset into countries with and without data
        $this->countriesWithData = array_filter($countriesData,function ($value){
            return $value !== null;
        });
        $this->countriesWithoutData = array_filter($countriesData, function ($value){
            return $value === null;
        });
        //can immediately populate the finalDataWasMissing list
        foreach($countriesData as $country => $data){
            $this->finalDataWasMissing[$country] = ($data===null);
        }

        //if missingdatahandlermethod is 'specificvalue'
        //then we need to apply that value to all countries missing data right away
        //so that the scores can be calculated to include that data value
        if ($missingDataHandlerMethod == 'specificValue' && $missingDataHandlerInput){
            foreach ($this->countriesWithoutData as $country => $noData){
                array_push($this->countriesWithData, $missingDataHandlerInput);
            }
            //now empty the countriesWithoutData array
            $this->countriesWithoutData = [];
        }

        //DECIDE WHAT TYPE OF CALCULATION TO DO
        //DEFAULT VS CUSTOM SCORE CALCULATION?
        if ($idealValue===null && !empty($customScoreFunction)){
            //USER WANTS TO DO CUSTOM SCORE CALCULATION
            $this->finalScores = ['errors'=>'Sorry, custom score calculations are not yet supported'];
        } elseif ($idealValue!==null && empty($customScoreFunction)){
            //USER WANTS TO DO DEFAULT SCORE CALCULATION
            //!DEFAULT SCORE CALCULATION HERE
            $this->calculateScoresDefault();
        } else {
            //USER HAS SENT INVALID INPUT DATA
            $this->finalScores = ['errors'=>'Invalid input data, must choose either idealValue or customScoreFunction, not both, and the one you arent using must be null'];
        }
    }
    /**
     * 
     */
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
    //DEFAULT calculation type entry point
    function calculateScoresDefault(){
        //deal with data type
        if ($this->datasetType =='boolean'){
            //TODO support boolean datatype calculations
            // $this->defaultCalculateBooleans();
            $this->finalScores = ['errors'=>'Sorry, boolean data type not currently supported'];
        } elseif ($this->datasetType == 'float' || $this->datasetType == 'double' || $this->datasetType == 'integer'){
            $this->defaultCalculateNumbers();
        } else {
            //invalid dataset type
            $this->finalScores= ['errors'=>'Unsupported data type given'.$this->datasetType];
        }
    }


    function defaultCalculateBooleans(){
        //TODO
    }
    public function initScoreCalculate($specificValue){
        //calculate how similar the actual value is to the ideal value (%)
        // using 100-(abs(thisdata-idealval)*1)/onepercent
        //calculate onepercent using
        //(range * 1.0) / 100.0;
        // multiply the score by the weight
        //MAX SCORE is 100*100 (10,000) which would be 100 weight x 100% similarity to ideal value

        $onePercent = ($this->dataMagnitude*1.0)/$this->maxInitScore;
        $percentSimilarity = $this->maxInitScore-(abs($specificValue - $this->idealValue)*1.0)/$onePercent;
        return $percentSimilarity;
    }
    function getInterpolatedScores(){
        //first rank all countries according to init score
        arsort($this->initScores);//now first item should have highest possible score, and last item worst
        $totalCountries = count($this->initScores);
        $rank = $totalCountries;
        $interpolatedScores = [];
        foreach ($this->initScores as $country=>$initScore){
            $normalizedScore = ($this->maxInitScore/$totalCountries*1.0)*$rank;
            $interpolatedScore = 
                $normalizedScore + 
                ($this->normalizationPercentage-100.0)*
                (($initScore-$normalizedScore)/(0.0-100.0));
            $interpolatedScores[$country] = $interpolatedScore;
            $rank --;
        }
        // Log::info($interpolatedScores);
        return $interpolatedScores;
    }
    function defaultCalculateNumbers(){
        //handle countries WITH data
        foreach ($this->countriesWithData as $country=>$value){
            //calculate the init scores
            $this->initScores[$country] = $this->initScoreCalculate($value);
        }
        //calculate the normalized scores (IF normalizationPercentage is >0)
        if ($this->normalizationPercentage >0){
            $interpolatedScores =  $this->getInterpolatedScores();
        } else {
            $interpolatedScores = $this->initScores;
        }
        //multiply the final scores by the weight
        foreach ($interpolatedScores as $country => $score){
            $this->finalScores[$country] = $score * $this->weight;
        }
        
        //handle countries WITHOUT data
        $this->handleCountriesWithoutDataNumeric();

    }
    function setAllCountriesWithoutDataTo($dataValue){
        foreach($this->countriesWithoutData as $country=>$data){
            $this->finalScores[$country] = $dataValue;
        }
    }
    
    function handleCountriesWithoutDataNumeric(){
        $missingDataHandler = new MissingDataHandler();
        $resultValue = $missingDataHandler->getScoreNumeric($this->finalScores,$this->missingDataHandlerMethod,$this->missingDataHandlerInput,);
        $this->setAllCountriesWithoutDataTo($resultValue);
    }
}

