<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Dataset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MissingDataHandler {
    protected $masterMethodList = [
        'average',
        'median',
        'mostFrequent',
        'worseThanPercentage',
        'betterThanPercentage',
        'specificScore',
        'specificValue',
    ];
    protected $masterMethodObject =[
        'average'=> [
            'formattedName' => 'Average',
            'requiresInput' => false,
            'description' => 'Countries with missing data get the average score of all the countries that did have data.',
        ],
        'median'=> [
            'formattedName' => 'Median',
            'requiresInput' => false,
            'description' => 'Countries with missing data get the middle-most score of all countries that did have data.'
        ],
        'mostFrequent'=>[
            'formattedName' => 'Most Frequent',
            'requiresInput' => false,
            'description' => 'Countries with missing data get the most frequently occuring score of all countries that did have data.'
        ],
        'worseThanPercentage'=>[
            'formattedName' => 'Worse-Than Percentage',
            'requiresInput' => true,
            'description' => 'Countries with missing data will get a score worse than X percent of all countries that did have data.'
        ],
        'betterThanPercentage'=>[
            'formattedName'=> 'Better-Than Percentage',
            'requiresInput' => true,
            'description' => 'Countries with missing data will get a score better than X percent of all countries that did have data.'
        ],
        'specificScore'=>[
            'formattedName' => 'Specific Score',
            'requiresInput' => true,
            'description' => 'Countries with missing data will get a score of X'
        ],
        'specificValue'=>[
            'formattedName' => 'Specific Value',
            'requiresInput' => true,
            'description' => 'Countries with missing data will be treated as if they did have data and that data was equal to X.'
        ]
        ];

    public function arrayWithAll(){
        return $this->masterMethodList;
    }
    public function objectWithAll(){
        return $this->masterMethodObject;
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
    public function getScoreNumeric(array $existingScoresData,string $missingDataHandlerMethod,?float $missingDataHandlerInput){
        asort($existingScoresData);//sorted smallest to largest
        $currentVals = $existingScoresData;
        $onePercentIndex = (count($currentVals)*1.0)/100.0;
        $inputParam = $missingDataHandlerInput;
        $resultValue;
        switch ($missingDataHandlerMethod){
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
            
            default:
            return die('error: missingdatahandlermethod not found');
        }
        return (int) $resultValue;
    }
}

class DatasetScores {
    //internal properties used by this class
    protected $originalDataset;
    protected $missingDataHandlerMethod;
    protected $missingDataHandlerInput;
    protected $datasetType;
    protected $idealValue;
    protected $customScoreFunction;

    protected $finalScores;
    protected $finalDataWasMissing;

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
        $this->dataMagnitute = abs($originalDataset['max_value']-$originalDataset['min_value']);
        $this->datasetWeight = $weight;
        $this->normalizationPercentage = $normalizationPercentage;
        $this->maxInitScore = 100; // represents precentage
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
    //primary return function
    public function getScoresObject(){
        //compile the data into the final structure
        //calculate the ranks and percentiles for each country
        //at the same time        
        arsort($this->finalScores);
        $currentRank = 1;
        $returnObject = [];
        foreach ($this->finalScores as $country =>$score){
            $percentile = ($currentRank/(count($rankingArray)+1.0))*100.0;
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

        $onePercent = ($this->dataMagnitute*1.0)/$this->maxInitScore;
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

class ScoresController extends Controller
{
    //this responds with an object
    //containing all country alpha-three-codes as keys
    //each country key holds another object that countains
    //the country's primary name, total score, the relative ranking, and the per-dataset score breakdown for this country
    //! ABOVE Is OUTDATED
    //! SEE the github wiki for theplaceforme-backend > API DOCUMENTATION for the required
    //!input and output object formats for this controller
    public static function getScores(Request $request){
        //input object must be in this form:
        // [{
        //     id: '',
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
        //     normalizationPercentage: 
                //used to determine how much normalization should be applied to the dataset prior to score calculation
        // },
        // {next dataset...}
        // ]
        

        //!SETUP
        $inputDatasets = $request->all();
        //!Validation
        $possibleDatasetIDs = array_map(function($arr){return $arr['id'];},Dataset::select('id')->whereNotNull('id')->get()->toArray());
        $maxAndMinFromDB = Dataset::select('id','max_value','min_value')->whereNotNull('id')->get()->toArray();//array of arrays, the sub arrays have string keys and values as values
        $maxAndMinValues;
        //convert the sql return array into more use-able format with id's as keys
        foreach ($maxAndMinFromDB as $subArray){
            $maxAndMinValues[$subArray['id']] = ['max_value'=>$subArray['max_value'],'min_value'=>$subArray['min_value']];
        }
        $missingDataHandler = new MissingDataHandler();
        $possibleMissingDataHandlerMethods = $missingDataHandler->arrayWithAll();
        //validate per dataset
        foreach ($inputDatasets as $dataset){
            $validator = Validator::make($dataset,
            [
                'id' => ['required',Rule::in($possibleDatasetIDs)],
                'weight' => ['required','integer','min:0','max:100'],
                'idealValue'=>[
                    // Rule::required_if(empty($dataset['customScoreFunction']))//!change when implementing customScoreFunction
                    'numeric'
                    ,'max:'.$maxAndMinValues[$dataset['id']]['max_value']
                    ,'min:'.$maxAndMinValues[$dataset['id']]['min_value']
                ],
                // 'customScoreFunction' => ['nullable']//!add customScoreFunction when able
                'missingDataHandlerMethod'=>['required',Rule::in($possibleMissingDataHandlerMethods)],
                'missingDataHandlerInput'=>['nullable'],
                'normalizationPercentage'=>['required','integer','min:0','max:100'],
            ]);
            if ($validator->fails()){
                return response()->json($validator->messages(),400);
            }
        }
        



        $responseObject = [];//see above the function for description of this object
        //populate the response object with each country and their names
        $countries = Country::select('alpha_three_code','primary_name')->where('alpha_three_code','!=',null)->get();
        foreach ($countries as $country){
            $code= $country['alpha_three_code'];
            $responseObject[$code]= [
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
            $missingDataHandlerInput = $dataset['missingDataHandlerInput'];
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
            // Log::info($responseObject);
            foreach ($scores as $country=>$score){
                //set this data to the per-dataset score breakdown
                $responseObject[$country]['scoreBreakdown'][$id] = $score; //sets the 'score', 'rank', 'percentage', et.c all at once
                //update the category breakdowns
                $currentCategoryScore = $responseObject[$country]['categoryBreakdown'][$dataset['category']];
                if ($currentCategoryScore){
                    //its initialized, just add the new score to it
                    $responseObject[$country]['categoryBreakdown'][$dataset['category']] = $currentCategoryScore + $score['score'];
                }else {
                    //initialize it with this score
                    $responseObject[$country]['categoryBreakdown'][$dataset['category']] = $score['score'];
                }
                //add this data to the overall total score for each country
                $responseObject[$country]['totalScore'] +=$score;
            }
        }

        //get relative rankings for each country now that they all have final scores
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
        //get the percentile also
        $percentile = ($currentRank/(count($rankingArray)+1.0))*100.0;
        $responseObject[$country]['percentile'] = $percentile;
        $currentRank ++;
        }
        


        //now return the response object
        return response()->json($responseObject,200);
    }

    public function getMissingDataHandlerMethods(Request $request){
        $missingDataHandler = new MissingDataHandler();
        $methodsList = $missingDataHandler->objectWithAll();
        return response()->json($methodsList,200);
    }
}
