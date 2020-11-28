<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class MissingDataHandler extends Model
{
    use HasFactory;
    protected $methodFunctions;
    public function getScore(Object $params)
    { //may mutate the params.existingScores by sorting them
        $existingScores = $params->existingScores;
        $method = $params->method;
        $inputValue = $params->inputValue;
        $dataType = $params->dataType || 'numeric'; //NOT USED CURRENTLY, AS THERE IS NO SUPPORT FOR NON NUMERIC DATA TYPES
        asort($existingScores); //sorted smallest to largest
        return (int) $this->methodFunctions[$method]($existingScores, $inputValue);
    }
    function __construct(){
    $this->methodFunctions = [
            'average' => function ($currentScores, $inputVal) {
                return array_sum($currentScores) / count($currentScores);
            },
            'median' => function ($currentScores, $inputVal) {
                return $this->median($currentScores);
            },
            'mostFrequent' => function ($currentScores, $inputVal) {
                return $this->mode($currentScores);
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

    function median($numbers = array())
    {
        if (!is_array($numbers))
            $numbers = func_get_args();

        rsort($numbers);
        $mid = (count($numbers) / 2);
        return ($mid % 2 != 0) ? $numbers[$mid - 1] : (($numbers[$mid - 1]) + $numbers[$mid]) / 2;
    }
    function mode($arr)
    {
        $values = array();
        foreach ($arr as $v) {
            if (isset($values[$v])) {
                $values[$v]++;
            } else {
                $values[$v] = 1;  // counter of appearance
            }
        }
        arsort($values);  // sort the array by values, in non-ascending order.
        $modes = array();
        $x = $values[key($values)]; // get the most appeared counter
        reset($values);
        foreach ($values as $key => $v) {
            if ($v == $x) {   // if there are multiple 'most'
                $modes[] = $key;  // push to the modes array
            } else {
                break;
            }
        }
        //if the array contains more than one value extract the median value (NOT AN AVERAGE)
        if (count($modes) > 1) {
            //if there are an even number of items in the array the median function
            //will return an average, not an actual value in the array, so
            //make sure the array has an odd number of values
            if (!(count($modes) % 2 > 0)) {
                array_pop($modes);
            }
            return $this->median($modes);
        } else {
            //otherwise just get the single value and return that
            return $modes[0];
        }
    }
}
