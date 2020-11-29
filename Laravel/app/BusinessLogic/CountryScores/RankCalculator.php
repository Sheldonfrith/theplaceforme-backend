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
        $arrayCopy = clone ($array);
        if ($higherValsAreBetter) {
            arsort($arrayCopy);
        } else {
            asort($arrayCopy);
        } // first values are the highest ranking always
        $rank = 0;
        return array_map(function ($val) use ($rank) {
            $rank++;
            return $rank;
        }, $arrayCopy);
    }
}
