<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class MissingDataHandler extends Model
{
    use HasFactory;
    protected $methodFunctions;
    public function getScore($params)
    { //may mutate the params.existingScores by sorting them
        $existingScores = $params['existingScores'];
        $method = $params['method'];
        $inputValue = $params['inputValue'];
        $dataType = $params['dataType'] || 'numeric'; //NOT USED CURRENTLY, AS THERE IS NO SUPPORT FOR NON NUMERIC DATA TYPES
        asort($existingScores); //sorted smallest to largest
        return (int) $this->methodFunctions[$method]($existingScores, $inputValue);
    }
    function __construct(){
    $this->methodFunctions = [
            'average' => function ($currentScores, $inputVal) {
                return array_sum($currentScores) / count($currentScores);
            },
            'median' => function ($currentScores, $inputVal) {
                return median($currentScores);
            },
            'mostFrequent' => function ($currentScores, $inputVal) {
                return mode($currentScores);
            },
            'worseThanPercentage' => function ($currentScores, $inputVal) {
                $closestIndex = $this->getClosestIndex($currentScores, $inputVal);
                return $currentScores[count($currentScores) - $closestIndex - 1] - 1;
            },
            'betterThanPercentage' => function ($currentScores, $inputVal) {
                $closestIndex = $this->getClosestIndex($currentScores, $inputVal);
                return $currentScores[$closestIndex] + 1;
            },
            'specificScore' => function ($currentScores, $inputVal) {
                return $inputVal;
            },
        ];
    }
    

    protected function getClosestIndex($currentScores, $inputVal)
    {
        $onePercentIndex = (count($currentScores) * 1.0) / 100.0;
        return round($onePercentIndex * $inputVal);
    }

    
}
