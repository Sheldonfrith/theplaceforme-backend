<?php

namespace App\BusinessLogic\CountryScores;


class RankCalculator
{
    public function __construct()
    {
    }
    public function getCountryCodesWithRanks($scoresByCountryCode)
    {
        return $this->arrayReplaceValuesWithRanks(true, $scoresByCountryCode);
    }
    public function arrayReplaceValuesWithRanks(bool $higherValsAreBetter, $array)
    {
        if ($higherValsAreBetter) {
            arsort($array);
        } else {
            asort($array);
        } // first values are the highest ranking always
        $rank = 0;
        $returnArray = [];
        foreach($array as $key=>$val){
            $rank++;
            $returnArray[$key]=$rank;
        }
        return $returnArray;
    }
}
