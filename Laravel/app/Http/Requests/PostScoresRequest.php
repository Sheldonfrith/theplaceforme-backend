<?php

namespace App\Http\Requests;

use App\Http\Requests\AbstractRequest;
use Illuminate\Validation\Rule;
use App\Models\Dataset;
use App\Models\MissingDataHandler;
use Illuminate\Support\Facades\Log;
class PostScoresRequest extends AbstractRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }


    // protected $routeParametersToValidate = [];
//     protected $queryParametersToValidate = [
//         'empty_response' => 'empty_response',
//         'save'=>'save', 
//         'name'=>'save_name',
//         'description'=>'save_description',
//         'user_id'=>'user_id',
// ];
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $possibleMissingDataHandlerMethods = MissingDataHandler::pluck('method_name');
        $possibleDatasetIDs = Dataset::pluck('id');
        $universalRules = [
            '*.id' =>['required',Rule::in($possibleDatasetIDs)],
                '*.category' =>['required','string'],
                '*.weight' =>['required','integer','min:0','max:100'],
                '*.missingDataHandlerMethod'=>['required',Rule::in($possibleMissingDataHandlerMethods)],
                '*.missingDataHandlerInput'=>['nullable'],
                '*.normalizationPercentage'=>['required','integer','min:0','max:100'],
        ];
        // $queryRules= [//query params
        //     'empty_response'=>['nullable','boolean'],
        //     'save'=>['nullable','boolean'],
        //     'save_name'=>['nullable','string'],
        //     'save_description'=>['nullable','string'],
        //     'user_id'=>['nullable','string'],
        // ];
        $minVals = Dataset::pluck('min_value','id')->toArray();
        $maxVals = Dataset::pluck('max_value','id')->toArray();
        $dynamicRules = [];
        foreach ($this->request as $key=>$val){
            $id = $val['id'];
            $dynamicRules[$key.'.idealValue'] =['numeric','max:'.$maxVals[$id],'min:'.$minVals[$id]] ;
            // array_merge($universalRules, [
               
            // ]);
        }
        return array_merge($universalRules, $dynamicRules);
    }
}
