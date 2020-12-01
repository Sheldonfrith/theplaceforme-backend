<?php

namespace App\BusinessLogic\CountryScores;

use App\Models\Country;
use Illuminate\Support\Arr;

class CountryContext
{
    protected $allCountryCodes;
    protected $countryCount;
    public function _construct()
    {
        $this->allCountryCodes = $this->getAllCountryCodes();
        $this->countryCount = count($this->allCountryCodes);
    }
    public function getAllCountryCodes()
    {
        if ($this->allCountryCodes && count($this->allCountryCodes)>0) return $this->allCountryCodes;
        return Country::pluck('alpha_three_code')->toArray();
    }
    public function getCountryCount(){
        return $this->countryCount;
    }
}