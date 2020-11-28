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