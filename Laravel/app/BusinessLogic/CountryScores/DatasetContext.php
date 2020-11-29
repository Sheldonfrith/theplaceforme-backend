<?php

namespace App\BusinessLogic\CountryScores;

use Illuminate\Support\Arr;
use App\Models\Dataset;

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
        $this->wholeDataset = Dataset::where('id', $datasetID)->get()->toArray();
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
    public function getCountryNamesWithoutData()
    {
        [$keys, $vals] = Arr::divide($this->countriesWithoutData);
        return $keys;
    }
    protected function separateMetadataFromCountryData($dataset, $countryCodes)
    {
        //1st object in return array is metada, second is the rest
        $testingFunction = function ($val, $key) use ($countryCodes) {
            return (in_array($key, $countryCodes)) ? false : true;
        };
        return arrayFilterGetBoth($dataset, $testingFunction);
    }
}
