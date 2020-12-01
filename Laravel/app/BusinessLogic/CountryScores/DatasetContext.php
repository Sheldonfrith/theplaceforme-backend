<?php

namespace App\BusinessLogic\CountryScores;

use Illuminate\Support\Arr;
use App\Models\Dataset;
use Illuminate\Support\Facades\Log;
class DatasetContext
{
    protected $wholeDataset;
    protected $metadata;
    protected $countryData;
    protected $countryContext;
    protected $countriesWithoutData;
    protected $countriesWithData;
    protected $dataMagnitude;

    public function __construct($datasetID, $countryContext)
    {
        $this->countryContext = $countryContext;
        $this->wholeDataset = Dataset::where('id', $datasetID)->get()->toArray()[0];
        [$this->metadata, $this->countriesData] = $this->separateMetadataFromCountryData($this->wholeDataset, $this->countryContext->getAllCountryCodes());
        [$this->countriesWithData, $this->countriesWithoutData] = arrayFilterGetBoth($this->countriesData, function ($val, $key) {
            return $val !== null;
        });
        $this->dataMagnitude = abs($this->metadata['max_value'] - $this->metadata['min_value']);
    }
    public function getCountriesWithoutData()
    {
        return $this->countriesWithoutData;
    }
    public function getCountriesWithData()
    {
        return $this->countriesWithData;
    }
    public function getDatasetMagnitude(){
        return $this->dataMagnitude;
    }
    public function getCountryNamesWithoutData()
    {
        [$keys, $vals] = Arr::divide($this->countriesWithoutData);
        return $keys;
    }
    protected function separateMetadataFromCountryData($dataset, $countryCodes)
    {
        //1st object in return array is metada, second is the rest
        $testingFunction = function ($val, $key) use ($countryCodes) {
            $isACountryField = (in_array($key,$countryCodes));
            return $isACountryField ? false : true;
        };
        // Log::info($dataset, $countryCodes);
        return arrayFilterGetBoth($dataset, $testingFunction);
    }
}
