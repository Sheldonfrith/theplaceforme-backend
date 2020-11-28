<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Dataset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\SavedScoresInput;
use App\Http\Requests\PostScoresRequest;
use App\BusinessLogic\CountryScores;
use App\Http\Resources\ScoresInputMetadata as ScoresInputMetadataResource;

class ScoresController extends Controller
{
    public function getScores(PostScoresRequest $request)
    { //should be a POST request to /scores
        $shouldSave = $request->query('save', true);
        if ($shouldSave === true) (new SavedScoresInput())->saveRequest($request);
        $emptyResponse = $request->query('empty_response', false);
        if ($emptyResponse) return;
        $responseObject = (new CountryScores())->calculateScores($request->json()->all());
        return response()->json($responseObject, 200);
    }


    public function getSavedScoresInputs(Request $request)
    { //should be a GET request to /scores
        $id = $request->query('id', null);
        $noScores = ($request->query('no_scores', false)) ? true : false;
        $validIDProvided = ($id && SavedScoresInput::where('id', $id)->get()[0]);
        if ($validIDProvided) return $this->respondWithSingleSavedScoresInput($id, $noScores);
        return $this->respondWithListOfSavedScoresInputs($request);
    }
    protected function respondWithSingleSavedScoresInput($id, bool $noScores)
    {
        $thisSavedScoresInput = SavedScoresInput::where('id', $id)->get();
        $savedScoresInputMetadata = new ScoresInputMetadataResource($thisSavedScoresInput);
        $savedScoresInputOriginalRequest = $thisSavedScoresInput['object'];
        $newCalculatedScores = (!$noScores) ? (new CountryScores())->calculateScores($savedScoresInputOriginalRequest) : null;
        $responseObject = [
            $savedScoresInputMetadata,
            $savedScoresInputOriginalRequest,
            $newCalculatedScores,
        ];
        return response()->json($responseObject, 200);
    }
    protected function respondWithListOfSavedScoresInputs(Request $request)
    {
        $queryParams = [
            'domain' => $request->query('domain'),
            'domainIncludes' => $request->query('domainIncludes'),
            'userID' => $request->query('userID'),
            'name' => $request->query('name'),
        ];
        $matchesTheseConditions = [];
        foreach ($queryParams as $name => $value) {
            if ($value) array_push($matchesTheseConditions, [$name, $value]);
        }
        $responseList = SavedScoresInput::select(
            'id',
            'created_at',
            'domain',
            'name',
            'description',
            'user_id',
        )->where($matchesTheseConditions)->get();
        if (!$responseList)  return response()->text('could not locate any Scores Inputs records corresponding to your input parameters', 400);
        return response()->json($responseList, 200);
    }
}
