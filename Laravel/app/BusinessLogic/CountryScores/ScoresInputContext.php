<?php

namespace App\BusinessLogic\CountryScores;

class ScoresInputContext
{
    protected $inputObject;
    public function __construct($scoresInputObject)
    {
        $this->inputObject = $scoresInputObject;
    }
    public function getDatasetByID($datasetID)
    {
        $indexOfDataset = null;
        foreach ($this->inputObject as $index => $object) {
            if ($object['id'] === $datasetID) $indexOfDataset = $index;
        }
        return $this->inputObject[$indexOfDataset];
    }
    public function getActiveDatasetIDs()
    {
        //active dataset meaning datasets with weight >0
        $idList = [];
        foreach ($this->inputObject as $object) {
            if ($object['weigth'] > 0) array_push($idList, $object['id']);
        }
        return $idList;
    }
    public function getDatasetCategoryByID($datasetID)
    {
        return $this->getDatasetByID($datasetID)['category'];
    }
}
