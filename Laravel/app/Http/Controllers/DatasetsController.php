<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Country;
use Facades\App\Models\Country as CountryFacade;
use App\Models\Dataset;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DatasetsController extends Controller
{
    protected $possibleCategories = [
        'demographics',
        'geography',
        'violence',
        'religion',
        'government',
        'economics',
        'immigration',
        'culture',
        'health',
        'environment',
        'travel',
        'education',
        'technology',
        'uncategorized',
    ];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $responseObject = Dataset::select(
            'id',
            'updated_at',
            'long_name',
            'data_type',
            'max_value',
            'min_value',
            'source_link',
            'source_description',
            'unit_description',
            'notes',
            'category',
            'distribution_map',
            'missing_data_percentage',
        )->where('id','!=',null)->get();
        return response()->json($responseObject,200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //set up the response object, default values ready for sending errors
        $response  = new Response();
        $response->headers->set('Content-Type','text/plain');

        //get the body
        $body = $request->all();
        if (empty($body)) {
            $response->setContent('Body was detected as empty');
            $response->setStatusCode(400);
            return $response;
        }
        //get the meta fields
        $meta = $body['meta'];
        if (empty($meta)){
            $response->setContent('Meta field was detected as empty');
            $response->setStatusCode(400);
            return $response;
        }
        //early, quick validation of meta fields
        $validator = Validator::make($meta,[
            'long_name'=> 'required|string',
            'data_type'=> 'required|string',
            'country_id_type' => 'required|string',
            'unit_description'=>'required|string',
            'notes' => 'nullable|string',
            'category'=>['required','string',Rule::in($this->possibleCategories)],
        ]);
        if ($validator->fails()){
            return response()->json($validator->messages(),400);
        }

        //get the datatype, required for validation, and send an error if its not found
        $data_type = $meta['data_type'];
        if (empty($data_type)){
            $response->setContent('data_type field of meta object was invalid');
            $response->setStatusCode(400);
            return $response;
        }
        // get the country_id_type, required for validation, and send an error if its not found
        $country_id_type = $meta['country_id_type'];
        $possibleCountryIdTypes = ['alpha_three_code','alpha_two_code','numeric_code','primary_name'];
        if (empty($country_id_type) || !in_array($country_id_type,$possibleCountryIdTypes)){
            $response->setContent('country_id_type field of meta object was invalid');
            $response->setStatusCode(400);
            return $response;
        }
        //create the validation array to pass to the 'validate' method for all countries which should be included
        $countries = Country::all()->where('alpha_three_code','!=',null);
        $countriesValidation = [];
        foreach ($countries as $country){
            $countryID = $country[$country_id_type];
            $dataTypeValidation = '';
            switch ($data_type){
                case 'boolean':
                    $dataTypeValidation = 'boolean';
                break;
                case 'float':
                    $dataTypeValidation = 'numeric';
                break;
                case 'double':
                    $dataTypeValidation = 'numeric';
                break;
                case 'integer':
                    $dataTypeValidation = 'integer';
                break;
                default:
                    $response ->setContent('Data Type meta field not valid');
                    $response ->setStatusCode(400);
                    return $response;
            }
            $countriesValidation[$countryID] = 'bail|present|nullable|'.$dataTypeValidation;
        }
        //perform actual validation here:
        $initialValidationArray = array_merge([
            'meta.source_link'=> [empty($meta['source_description'])?'required':'nullable','url'],
            'meta.source_description' => [empty($meta['source_link'])?'required':'nullable','string'],
        ],$countriesValidation);
        // return $initialValidationArray;
        $validator = Validator::make($body,$initialValidationArray);
        if ($validator->fails()){
            return response()->json($validator->messages(),400);
        }
        //ALL DATA IS NOW FULLY VALIDATED
        unset($body['meta']);// remove meta object from dataset for iteration
        //change the country id type of the data to alpha 3 if not already
        $countryData = [];
        if ($country_id_type !== 'alpha_three_code'){
            foreach ($body as $key => $value){
                $newKey = CountryFacade::convertAttribute($country_id_type,$key,'alpha_three_code');
                $countryData[$newKey] = $value;
            }
        } else {
            $countyData = $body;
        }
        //calculate the min/max values if the datatype is numeric or boolean
        if ($data_type==='float' || $data_type==='double' || $data_type==='integer'){
            //data type IS numeric
            //remove null values before calculating min and max
            $deNullified = array_diff($body, array(null));
            $minValue = min($deNullified);
            $maxValue = max($deNullified);
        } elseif($data_type==='boolean') {
            $minValue = false;
            $maxValue = true;
        } else {
            $minValue = null;
            $maxValue = null;
        }
        //calculate the distribution map of the dataset
        //this is done by dividing the total dataset (from min_value to max_value)
        //into 101 equally distributed ranges (0% -100%)
        //and counting how many countries fall into each range
        //then outputting the total country count for each range into 100-item simple list
        $distributionMap = array_fill(0,101,0);
        foreach ($deNullified as $value){
            $index = null;
            $index = (int) round(($value-$minValue)*100.0/($maxValue-$minValue)); //index is the percentage of the value relative to the dataset range rounded to nearest integer
            $distributionMap[$index] ++;
        }
        
        //Send the new dataset to the database
        Dataset::create(array_merge([
            'long_name' => $meta['long_name'],
            'data_type' => $meta['data_type'],
            'max_value'=> $maxValue,
            'min_value'=> $minValue,
            'source_link' => @$meta['source_link'],
            'source_description' => @$meta['source_description'],
            'unit_description' => $meta['unit_description'],
            'notes'=> @$meta['notes'],
            'category' => $meta['category'],
            'distribution_map' => $distributionMap,
        ],$countryData));

        //send a response indicating success
        $response->setContent('Successfully added this dataset to the database!');
        $response->setStatusCode(201);
        return $response;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function listPossibleCategories(){
        return response()->json($this->possibleCategories,200);
    }
}
