<?php

namespace App\BusinessLogic\CountryScores;

class PercentileCalculator
{
    public function __construct()
    {
    }
    public function getPercentile(bool $higherValuesAreBetter, $minValue, $maxValue, $value)
    {
        if (!$higherValuesAreBetter) {
            $min = $maxValue;
            $max = $minValue;
        } else {
            $min = $minValue;
            $max = $maxValue;
        }
        return (($value - $min) / ($max - $min)) * 100.0;
    }
    public function arrayReplaceValuesWithPercentiles(bool $higherValsAreBetter, $array)
    {
        $min = min($array);
        $max = max($array);
        return array_map(function ($val) use ($min, $max, $higherValsAreBetter) {
            return $this->getPercentile($higherValsAreBetter, $min, $max, $val);
        }, $array);
    }
}
