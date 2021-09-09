<?php

namespace App\Http\Controllers;

use App\Events\DatasetSubmitted;
use App\Http\Requests\PostDatasetRequest;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Dataset;
use App\Http\Resources\DatasetsMeta as DatasetsMetaResource;
use Illuminate\Http\Response;


class DatasetsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return DatasetsMetaResource::collection(Dataset::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PostDatasetRequest $request)
    {
        $body = $request->all();
        $meta = $body['meta'];
        unset($body['meta']);// remove meta object from dataset for iteration
        //change the country id type of the data to alpha 3 if not already
        $countryData = [];
        if ($meta['country_id_type'] !== 'alpha_three_code'){
            foreach ($body as $key => $value){
                $countryInstance = new Country();
                $newKey = $countryInstance->convertAttribute($meta['country_id_type'],$key,'alpha_three_code');
                $countryData[$newKey] = $value;
            }
        } else {
            $countyData = $body;
        }
        $newDataset = Dataset::create(array_merge($meta,$countryData));
        $response  = new Response('Successfully submitted this dataset for review',201,['Content-Type'=>'text/plain']);
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
        return response()->json(config('constants.catogeries.allowed_names'),200);
    }
}
