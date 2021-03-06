<?php

use Illuminate\Support\Facades\Log;

if (!function_exists('convertSQLTypeToValidatorType')) {
    function convertSQLTypeToValidatorType($sqlType)
    {
        switch ($sqlType) {
            case 'float':
                return 'numeric';
            case 'double':
                return 'numeric';
            case 'integer':
                return 'integer';
            default:
                return '';
        }
    }
}
if (!function_exists('arrayFilterGetBoth')) {
    function arrayFilterGetBoth(array $inputArray, $testingFunction)
    {
        $passingArray=[]; $failingArray=[];
        foreach($inputArray as $key => $val){
            $testingFunctionPasses = $testingFunction($val, $key);
            if ($testingFunctionPasses){
                $passingArray[$key] = $val;
            } else {$failingArray[$key]= $val;}
        } 
        return [$passingArray, $failingArray];
    };
}

function median($numbers = array())
{
    if (!is_array($numbers)) $numbers = func_get_args();
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
        return median($modes);
    } else {
        //otherwise just get the single value and return that
        return $modes[0];
    }
}
function array_merge_sum_values (array $array1, array $array2){
    Log::info(count($array1));
    Log::info(count($array2));
    $returnArray = [];
    foreach($array1 as $key=>$val){
        $returnArray[$key] = $val+$array2[$key];
    }
    return $returnArray;
}