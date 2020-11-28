<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Country;

class PostDatasetRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return $this->getPostDatasetValidationRules();
    }

    protected function getPostDatasetValidationRules()
    {
        return array_merge($this->getMetaFieldsValidationRules(), $this->getCountriesValidationRules());
    }
    protected function getMetaFieldsValidationRules()
    {
        return [
            'meta.long_name' => 'required|string',
            'meta.data_type' => ['required', Rule::in_array(config('constants.datasets.supported_data_types'))],
            'meta.country_id_type' => ['required', Rule::in_array(config('constants.countries.possible_id_types'))],
            'meta.unit_description' => 'required|string',
            'meta.notes' => 'nullable|string',
            'meta.category' => ['required', 'string', Rule::in(config('constants.categories.allowed_names'))],
            'meta.source_link' => [empty($this->json['meta']['source_description']) ? 'required' : 'nullable', 'url'],
            'meta.source_description' => [empty($this->json['meta']['source_link']) ? 'required' : 'nullable', 'string'],
        ];
    }
    protected function getMetaFieldsValidationRules()
    {
        $dataTypeValidationName = convertSQLTypeToValidatorType($this->json['meta']['data_type']);
        $countries = Country::all()->where('alpha_three_code', '!=', null);
        $countriesValidation = [];
        foreach ($countries as $country) {
            $countryID = $country[$this->json['meta']['country_id_type']];
            $countriesValidation[$countryID] = 'bail|present|nullable|' . $dataTypeValidationName;
        }
        return $countriesValidation;
    }
}
