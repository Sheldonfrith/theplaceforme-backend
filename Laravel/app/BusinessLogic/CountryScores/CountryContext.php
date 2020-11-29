<?php

namespace App\BusinessLogic\CountryScores;

use App\Models\Country;
use Illuminate\Support\Arr;

class CountryContext
{
    public $allCountryCodes;
    public $countryCount;
    public function _construct()
    {
        $this->allCountryCodes = $this->getAllCountryCodes();
        $this->countryCount = count($this->allCountryCodes);
    }
    protected function getAllCountryCodes()
    {
        $unformatted = Country::select('alpha_three_code')->get()->toArray();
        [$keys, $values] = Arr::divide($unformatted)[1];
        return $values;
    }
}