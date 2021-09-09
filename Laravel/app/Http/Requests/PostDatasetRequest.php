<?php

namespace App\Http\Requests;

use App\Http\Requests\AbstractRequest;
use Illuminate\Validation\Rule;
use App\Models\Country;

class PostDatasetRequest extends AbstractRequest
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
            'meta.data_type' => ['required', Rule::in(config('constants.datasets.supported_data_types'))],
            'meta.country_id_type' => ['required', Rule::in(config('constants.countries.possible_id_types'))],
            'meta.unit_description' => 'required|string',
            'meta.notes' => 'nullable|string',
            'meta.category' => ['required', 'string', Rule::in(config('constants.categories.allowed_names'))],
            'meta.source_link' => ['nullable', 'url', 'required_if:meta.source_description,null'],
            'meta.source_description' => ['nullable', 'string', 'required_if:meta.source_link,null'],
        ];
    }
    protected function getCountriesValidationRules()
    {
        $dataTypeValidationName = convertSQLTypeToValidatorType($this->all()['meta']['data_type']);
        $countries = Country::all()->where('alpha_three_code', '!=', null);
        $countriesValidation = [];
        foreach ($countries as $country) {
            $countryID = $country[$this->all()['meta']['country_id_type']];
            $countriesValidation[$countryID] = 'bail|present|nullable|' . $dataTypeValidationName;
        }
        return $countriesValidation;
    }
}
