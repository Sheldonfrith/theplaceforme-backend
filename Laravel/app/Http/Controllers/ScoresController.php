<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Dataset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\SavedScoresInput;
use App\Models\MissingDataHandler;
use App\Models\ScoreCalculator;
use App\Models\CountryRankingsCalculator;

class ScoresController extends Controller
{

    //utility function used by both /scores POST and /scores GET
    

    /**
     * Refer to the link for more info
     * @link https://github.com/Sheldonfrith/theplaceforme-backend/wiki/API-Documentation
     * 
     * @global response
     * @global SavedScoresInput
     * @global Dataset
     * @global MissingDataHandler
     * 
     * @param Request $request
     * @return response JSON http response
     */
    public function getScores(Request $request){ //POST REQUEST
        //get request body, for use throughout this function
        $inputDatasets = $request->json()->all();
        //*VALIDATE the request body structure
        /** Required structure:
         * [obj, obj, obj ...] 
         * where...
         * obj = {
         *      id: integer, required
         *      category: string, required
         *      weight: integer, required
         *      idealValue: number, required
         *      customScoreFunction: any, optional !NOT USED CURRENTLY
         *      missingDataHandlerMethod: string, required
         *      missingDataHandlerInput: any, optional
         *      normalizationPercentage: integer, required
         * }
         */
        //first, is it an array?
        //!return with error if its not an array
        if (!is_array($inputDatasets)) return response()->json(['error, invalid input request body, not an array'],400);
        //it IS an array
        $possibleDatasetIDs = array_map(function($arr){return $arr['id'];},Dataset::select('id')->whereNotNull('id')->get()->toArray());
        //min and max used to validate the 'idealValue' field is within valid range
        $maxAndMinFromDB = Dataset::select('id','max_value','min_value')->whereNotNull('id')->get()->toArray();//array of arrays, the sub arrays have string keys and values as values
        $maxAndMinValues;
        //convert the sql return array into more use-able format with id's as keys
        foreach ($maxAndMinFromDB as $subArray){
            $maxAndMinValues[$subArray['id']] = ['max_value'=>$subArray['max_value'],'min_value'=>$subArray['min_value']];
        }

        $missingDataHandler = new MissingDataHandler(); // instance of MissingDataHandler to get the method names
        $possibleMissingDataHandlerMethods = $missingDataHandler->methodNames();
        //validate each object (per dataset)
        foreach ($inputDatasets as $dataset){
            $validator = Validator::make($dataset,
            [
                'id' => ['required',Rule::in($possibleDatasetIDs)],
                'category' => ['required','string'],
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
                //! return with error messages if validation fails
                return response()->json($validator->messages(),400);
            }
        }
        //* Get the Query params
        $emptyResponse = $request->query('empty_response', false);// boolean, if true scores will not be calculated and response will be empty array
        $shouldSave = $request->query('save', true);//boolean, if false WONT save the request body as a SavedScoresInput
        $saveName = $request-> query('name',null); //string, 'name' field for new SavedScoresInput
        $saveDescription = $request->query('description', null); //string, 'description' field for new SavedScoresInput
        $saveUserID = $request->query('user_id',null); // string, 'user_id' field for new SavedScoresInput
        $saveDomain = $request->root(); //string, auto-detected based on where the $request came from, saved to 'domain' field of new SavedScoresInput

        //* SAVE THE REQUEST
        if ($shouldSave === false || $shouldSave === 'false'){
            //dont save
        } else {
            //DO save
            SavedScoresInput::create([
                'domain' => $saveDomain,
                'name' => $saveName,
                'description' => $saveDescription,
                'user_id' =>$saveUserID,
                'object' => $inputDatasets,
            ]);
        }

        //*EMPTY RESPONSE?
        if ($emptyResponse === true || $emptyResponse === 'true'){
            return response()->json([],200);
        }

        //*CALCULATE SCORES 
        $countryRankingsCalculator = new CountryRankingsCalculator;

        $responseObject = $countryRankingsCalculator->getFormattedScoresObject($inputDatasets);
        
        //! return with success and calculated scores
        return response()->json($responseObject,200);
    }




    /**
     * handle GET request to Scores
     * fetch from SavedScoresInput (model) based on query params
     * 
     * @link https://github.com/Sheldonfrith/theplaceforme-backend/wiki/API-Documentation
     * @global SavedScoresInput
     * @global $this->CalculateScores
     * @global $response
     * @param Request $request 
     * @return Response $response returns a JSON http response
     * 
     */
    public function getSavedScoresInputs (Request $request){//GET REQUEST
        //get query params from the request
        $noScores = $request->query('no_scores',false);// boolean, determines if response should include calculated scores 
        $id = $request->query('id',null); // int, if id is valid return only the specific SavedScoresInput associated with that id
        $domain = $request->query('domain',null); // string, filter SavedScoresInputs by 'domain' field, exact matches only
        $domainIncludes = $request->query('domain_includes',null); // string, filter SavedScoresInputs by 'domain' field, any domain that includes this
        $userID = $request->query('user_id',null); // string, filter SavedScoresInputs by 'userID' field
        $name = $request->query('name',null); //string, filter SavedScoresInputs by 'name' field

        //first, attempt to retrieve by id...
        $thisSavedScoresInput = null;
        // get the whole row from the database
        if ($id) $thisSavedScoresInput = SavedScoresInput::where('id',$id)->get()[0];
        //// Log::info($thisSavedScoresInput);
        if ($thisSavedScoresInput){
            //$id was valid, SavedScoresInput found... return the object found
            //return is array with three objects
                // return [0] = ScoresInput metadata
                $returnList = [];
                $returnList[0] = [
                    'id' => $thisSavedScoresInput['id'],
                    'created_at' => $thisSavedScoresInput['created_at'],
                    'domain' =>$thisSavedScoresInput['domain'],
                    'name' => $thisSavedScoresInput['name'],
                    'description' => $thisSavedScoresInput['description'],
                    'user_id' => $thisSavedScoresInput['user_id'],
                ];
    
                // return [1] = ScoresInput 'object' field from database
                $returnList[1] = $thisSavedScoresInput['object'];

                // return [2] = null or calculated scores
                if ($noScores && $noScores !=='false'){
                    //DONT calculate or return scores
                    $returnList[2] = null;
                } else {
                    //should calculate and return scores
                    $returnList[2] = $this->CalculateScores($returnList[1]);
                }
                //! Return response here, single SavedScoresInput from ID
                return response()->json($returnList,200);
        } else {
            //No valid id found, try to return a list, possibly filtered based on query params
            //TODO restrict this list based on the API key / authorization of the user requesting
            //restrict based on query params
             //create the where conditions list
             $whereConditions = [];
             if ($domain) array_push($whereConditions, ['domain',$domain]);
             if ($domainIncludes) array_push($whereConditions, ['domain','like','%'+$domainIncludes+'%']);
             if ($userID) array_push($whereConditions, ['user_id',$userID]);
             if ($name) array_push($whereConditions,['name',$name]);
             
             $thisSavedScoresInput = SavedScoresInput::select(
                'id',
                'created_at',
                'domain',
                'name',
                'description',
                'user_id',
            )->where($whereConditions)->get();
            //!Return response list of SavedScoresInputs
            return response()->json($thisSavedScoresInput,200);
        }
        //!Return response error message
        return response()->json(['could not locate any Scores Inputs records corresponding to your input parameters'],400);
    }

    /**
     * GET request to /missing-data-handler-methods
     * @link https://github.com/Sheldonfrith/theplaceforme-backend/wiki/API-Documentation
     * @global response
     * @global MissingDataHandler
     * @param Request $request
     * @return response JSON http response, code 200
     * 
     */
    public function getMissingDataHandlerMethods(Request $request){
        $missingDataHandler = new MissingDataHandler();
        $methodsList = $missingDataHandler->methodsInfo();
        if ($methodsList){
            //probably found the methods list
            //TODO add validation
            return response()->json($methodsList,200);
        } else {
            //couldnt find the methods list
            return  response()->json(['Error: couldnt find the methods list, something wrong on our end.'],500);
        }
    }
}
