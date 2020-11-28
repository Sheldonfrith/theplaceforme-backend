<?php

if (! function_exists('convertSQLTypeToValidatorType')) {
    function convertSQLTypeToValidatorType($sqlType) {
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
if (! function_exists('arrayFilterGetBoth')){
    function arrayFilterGetBoth($inputArray, $testingFunction){
    $passingArray = array_filter($inputArray,$testingFunction,ARRAY_FILTER_USE_BOTH);
    $failingArray = array_diff($inputArray, $passingArray);
    return [$passingArray, $failingArray];
};
}
