<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Dataset;
use Illuminate\Support\Facades\Log;


class DatasetScores {
    //internal properties used by this class
    protected $originalDataset;
    protected $missingDataHandlerMethod;
    protected $missingDataHandlerInput;
    protected $datasetType;
    protected $idealValue;
    protected $customScoreFunction;
    protected $finalScoresObject;
    protected $countriesWithData;
    protected $countriesWithoutData;
    protected $dataMagnitude;
    protected $datasetWeight;
    protected $countryCodes;

    //constructor function
    function __construct(
        array $originalDataset,
        string $missingDataHandlerMethod,
        ?float $missingDataHandlerInput,
        int $weight,
        $idealValue,
        $customScoreFunction
        ){
        $this->originalDataset = $originalDataset;
        $this->missingDataHandlerInput = $missingDataHandlerInput;
        $this->missingDataHandlerMethod = $missingDataHandlerMethod;
        $this->datasetType = $originalDataset['data_type'];
        $this->idealValue = $idealValue;
        $this->customScoreFunction = $customScoreFunction;
        $this->dataMagnitute = abs($originalDataset['max_value']-$originalDataset['min_value']);
        $this->datasetWeight = $weight;
        $this->countryCodes = array_map(function($value){
            return $value['alpha_three_code'];
        },Country::select('alpha_three_code')->where('alpha_three_code','!=',null)->get()->toArray());


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

        //DECIDE WHAT TYPE OF CALCULATION TO DO
        //DEFAULT VS CUSTOM SCORE CALCULATION?
        if ($idealValue===null && !empty($customScoreFunction)){
            //USER WANTS TO DO CUSTOM SCORE CALCULATION
            $this->finalScoresObject = ['errors'=>'Sorry, custom score calculations are not yet supported'];
        } elseif ($idealValue!==null && empty($customScoreFunction)){
            //USER WANTS TO DO DEFAULT SCORE CALCULATION
            //!DEFAULT SCORE CALCULATION HERE
            $this->calculateScoresDefault();
        } else {
            //USER HAS SENT INVALID INPUT DATA
            $this->finalScoresObject = ['errors'=>'Invalid input data, must choose either idealValue or customScoreFunction, not both, and the one you arent using must be null'];
        }
    }
    //primary return function
    public function getScoresObject(){
        return $this->finalScoresObject;
    }
    //DEFAULT calculation type entry point
    function calculateScoresDefault(){
        //deal with data type
        if ($this->datasetType =='boolean'){
            //TODO support boolean datatype calculations
            // $this->defaultCalculateBooleans();
            $this->finalScoresObject = ['errors'=>'Sorry, boolean data type not currently supported'];
        } elseif ($this->datasetType == 'float' || $this->datasetType == 'double' || $this->datasetType == 'integer'){
            $this->defaultCalculateNumbers();
        } else {
            //invalid dataset type
            $this->finalScoresObject= ['errors'=>'Unsupported data type given'.$this->datasetType];
        }
    }


    function defaultCalculateBooleans(){
        //TODO
    }
    function defaultScoreCalculate($specificValue){
        //calculate how similar the actual value is to the ideal value (%)
        // using 100-(abs(thisdata-idealval)*1)/onepercent
        //calculate onepercent using
        //(range * 1.0) / 100.0;
        // multiply the score by the weight
        //MAX SCORE is 100*100 (10,000) which would be 100 weight x 100% similarity to ideal value
        $onePercent = ($this->dataMagnitute*1.0)/100.0;
        $percentSimilarity = 100.0-(abs($specificValue - $this->idealValue)*1.0)/$onePercent;
        $weightedScore = $this->datasetWeight*$percentSimilarity;
        return (int) $weightedScore;
    }
    function defaultCalculateNumbers(){
        //handle countries WITH data
        foreach ($this->countriesWithData as $country=>$value){
            //calculate the value and send it to the final arary
            $this->finalScoresObject[$country]= $this->defaultScoreCalculate($value);
        }
        //handle countries WITHOUT data
        $this->handleCountriesWithoutDataNumeric();

    }
    function setAllCountriesWithoutDataTo($dataValue){
        foreach($this->countriesWithoutData as $country=>$data){
            $this->finalScoresObject[$country] = $dataValue;
        }
    }
    function median($numbers=array()){
        if (!is_array($numbers))
            $numbers = func_get_args();

        rsort($numbers);
        $mid = (count($numbers) / 2);
        return ($mid % 2 != 0) ? $numbers[$mid-1] : (($numbers[$mid-1]) + $numbers[$mid]) / 2;
    }
    function mode($arr) {
        $values = array();
        foreach ($arr as $v) {
          if (isset($values[$v])) {
            $values[$v] ++;
          } else {
            $values[$v] = 1;  // counter of appearance
          }
        }
        arsort($values);  // sort the array by values, in non-ascending order.
        $modes = array();
        $x = $values[key($values)]; // get the most appeared counter
        reset($values);
        foreach ($values as $key => $v) {
          if ($v == $x) {   // if there are multiple 'most'
            $modes[] = $key;  // push to the modes array
          } else {
            break;
          }
        }
        //if the array contains more than one value extract the median value (NOT AN AVERAGE)
        if (count($modes)>1){
            //if there are an even number of items in the array the median function
            //will return an average, not an actual value in the array, so
            //make sure the array has an odd number of values
            if (!(count($modes)%2>0)){
                array_pop($modes);
            }
            return $this->median($modes);
        }else {
            //otherwise just get the single value and return that
            return $modes[0];
        }
          }
    function handleCountriesWithoutDataNumeric(){
        asort($this->finalScoresObject);//sorted smallest to largest
        $currentVals = $this->finalScoresObject;
        $onePercentIndex = (count($currentVals)*1.0)/100.0;
        $inputParam = $this->missingDataHandlerInput;
        $resultValue;
        switch ($this->missingDataHandlerMethod){
            case 'average':
                $resultValue = array_sum($currentVals)/count($currentVals);
            break;
            case 'median':
                $resultValue = $this->median($currentVals);
            break;
            case 'mostFrequent':
                $resultValue = $this->mode($currentVals);
            break;
            case 'worseThanPercentage': //returned value will be worse than x% of the countries WITH data
                $closestIndex = round($onePercentIndex*$inputParam);
                $resultValue = $currentVals[count($currentVals)-$closestIndex-1]-1;
            break;
            case 'betterThanPercentage'://returned value will be better than x% of the countries WITH data
                $closestIndex = round($onePercentIndex*$inputParam);
                $resultValue = $currentVals[$closestIndex]+1;
            break;
            case 'specificScore':
                $resultValue = $inputParam;
            break;
            case 'specificValue'://score for this calculated using default method as if inputparam were the actual data for the country
                $resultValue = $this->defaultScoreCalculate($inputParam);
            break;
            default:
            return die('error: missingdatahandlermethod not found');
        }
        $this->setAllCountriesWithoutDataTo((int) $resultValue);
    }
}

class ScoresController extends Controller
{
    //this responds with an object
    //containing all country alpha-three-codes as keys
    //each country key holds another object that countains
    //the country's long name, total score, the relative ranking, and the per-dataset score breakdown for this country
    public static function getScores(Request $request){
        //input object must be in this form:
        // [{
        //     datasetID: '',
        //     weight: '',
                //if weight is zero the dataset will be excluded from all score calculations
                //range of 0-100
        //     idealValue: '',
                //if this is set then we will do our own score calculation
                //cannot set both ideal value and customScoreFunction, its one or the other
                // must be within the min/max values for the dataset
        //     customScoreFunction: '',
                // a function that accepts a single input
                //(the current country's value for this dataset)
                //and returns an output score based on that
        //     missingDataHandlerMethod: '',
                //tells us how to handle the score calculation
                //when a country is missing data
                //possible methods include:
                    //average (set countries with no data to the average score of countries that did have data)
                    //median (set the countries with no data to the median of the countries that did have data)
                    //worseThanPercentage (set countries with no data to have lower scores (-1 lower) than X percent of
                            //countries that did have data... X determined by missingDataHandlerInput);
                    //betterThanPercentage (set countries with no data to have higher scores (+1 higher) than X percent of
                            //countries that did have data... X dertermind by missingDataHandlerInput);
                    //specificScore (set countries with no data to have a specific score always, score
                            // set by missingDataHandlerInput);
                    //specificValue (set countries with no data to have a specific data value for the dataset
                            //and then calculate the score the same as all the countries that did have data);
                    //mostFrequent (set country's with missing data's score to be the same as the most frequent score
                            //among the countries that did have data)
                    //
        //     missingDataHandlerInput: '',
                //used to pass parameters for more
                //advanced missingDataHandlerMethods (above)
        // },
        // {next dataset...}
        // ]

        //!SETUP
        $inputDatasets = $request->all();
        //!Validation
        //TODO
        $responseObject = [];//see above the function for description of this object
        //populate the response object with each country and their names
        $countries = Country::select('alpha_three_code','primary_name')->where('alpha_three_code','!=',null)->get();
        foreach ($countries as $country){
            $code= $country['alpha_three_code'];
            $responseObject[$code]= [
                'primary_name'=>$country['primary_name'],
                'totalScore'=>0,
                'rank'=>0,
                'scoreBreakdown'=>[],
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
            $missingDataHandlerInput = $dataset['missingDataHandlerInput'];

            //get the scores for this dataset
            $scoreCalculator = new DatasetScores(
                Dataset::where([['id','=',$dataset['id']],['id','!=',null]])->get()[0]->toArray(),
                $missingDataHandlerMethod,
                $missingDataHandlerInput,
                $weight,
                $idealValue,
                $customScoreFunction);
            $scores = $scoreCalculator->getScoresObject();
            //push the scores for this dataset
            // Log::info($responseObject);
            foreach ($scores as $country=>$score){
                //set this data to the per-dataset score breakdown
                $responseObject[$country]['scoreBreakdown'][$id] = $score;
                //add this data to the overall total score for each country
                $responseObject[$country]['totalScore'] +=$score;
            }
        }

        //Final step: get relative rankings for each country now that they all have final scores
        //first get a list with just the total scores, and country codes as keys
        $rankingArray ;
        foreach($countries as $country){
            $code = $country['alpha_three_code'];
            $rankingArray[$code] = $responseObject[$code]['totalScore'];
        }
        //sort that new list, highest scores are first on the list now
        arsort($rankingArray);
        $currentRank = 1;
        foreach($rankingArray as $country => $score){
        $responseObject[$country]['rank'] = $currentRank;
        $currentRank ++;
        }

        //now return the response object
        return response()->json($responseObject,200);
    }
}
