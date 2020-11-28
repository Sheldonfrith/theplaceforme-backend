<?php

namespace App\Http\Requests;

use App\Http\Requests\AbstractRequest;
use Illuminate\Validation\Rule;
use App\Models\Dataset;
use App\Models\MissingDataHandler;

class PostScoresRequest extends AbstractRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }


    protected $routeParametersToValidate = [];
    protected $queryParametersToValidate = [
        'empty_response' => 'empty_response',
        'save'=>'save', 
        'name'=>'save_name',
        'description'=>'save_description',
        'user_id'=>'user_id',
];
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $possibleDatasetIDs = Dataset::select('id')->whereNotNull('id')->get()->toArray();
        $possibleMissingDataHandlerMethods = MissingDataHandler::select('method_name')->get()->toArray();
        $id = $this->json()->id;
        $maxValue = Dataset::where('id',$id)->select('max_value')->get()->toArray()['max_value'];
        $minValue = Dataset::where('id',$id)->select('min_value')->get()->toArray()['min_value'];
        return [
            'id' =>['required',Rule::in($possibleDatasetIDs)],
            'category' =>['required','string'],
            'weight' =>['required','integer','min:0','max:100'],
            'idealValue'=>['numeric','max:'.$maxValue,'min:'.$minValue,],
            'missingDataHandlerMethod'=>['required',Rule::in($possibleMissingDataHandlerMethods)],
            'missingDataHandlerInput'=>['nullable'],
            'normalizationPercentage'=>['required','integer','min:0','max:100'],
            //query params
            'empty_response'=>['nullable','boolean'],
            'save'=>['nullable','boolean'],
            'save_name'=>['nullable','string'],
            'save_description'=>['nullable','string'],
            'user_id'=>['nullable','string'],
        ];
    }
}
