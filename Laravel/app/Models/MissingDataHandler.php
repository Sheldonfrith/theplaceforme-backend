<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

/**
 * Class for handling missing data and providing information about
 * missingDataHandlerMethods.
 * 
 * @method methodNames returns simple list with names of allowed missingDataHandlerMethods.
 * @method methodsInfo returns an object with method names as keys and meta info object as values.
 * @method getScoreNumeric return a score calculated based on inputs, to be used in place of real score for a country.
 * 
 */
class MissingDataHandler {
    protected $masterMethodList = [
        'average',
        'median',
        'mostFrequent',
        'worseThanPercentage',
        'betterThanPercentage',
        'specificScore',
        'specificValue',
    ];
    protected $masterMethodObject =[
        'average'=> [
            'formattedName' => 'Average',
            'requiresInput' => false,
            'description' => 'Countries with missing data get the average score of all the countries that did have data.',
        ],
        'median'=> [
            'formattedName' => 'Median',
            'requiresInput' => false,
            'description' => 'Countries with missing data get the middle-most score of all countries that did have data.'
        ],
        'mostFrequent'=>[
            'formattedName' => 'Most Frequent',
            'requiresInput' => false,
            'description' => 'Countries with missing data get the most frequently occuring score of all countries that did have data.'
        ],
        'worseThanPercentage'=>[
            'formattedName' => 'Worse-Than Percentage',
            'requiresInput' => true,
            'description' => 'Countries with missing data will get a score worse than X percent of all countries that did have data.'
        ],
        'betterThanPercentage'=>[
            'formattedName'=> 'Better-Than Percentage',
            'requiresInput' => true,
            'description' => 'Countries with missing data will get a score better than X percent of all countries that did have data.'
        ],
        'specificScore'=>[
            'formattedName' => 'Specific Score',
            'requiresInput' => true,
            'description' => 'Countries with missing data will get a score of X'
        ],
        'specificValue'=>[
            'formattedName' => 'Specific Value',
            'requiresInput' => true,
            'description' => 'Countries with missing data will be treated as if they did have data and that data was equal to X.'
        ]
        ];

    /**
     * Calculates the median value of a given set of numbers.
     * 
     * @param array $numbers Simple array of numbers.
     * @return float The median of input array. 
     */
    protected function median(array $numbers): float{
        if (!is_array($numbers))
            $numbers = func_get_args();

        rsort($numbers);
        $mid = (count($numbers) / 2);
        return ($mid % 2 != 0) ? $numbers[$mid-1] : (($numbers[$mid-1]) + $numbers[$mid]) / 2;
    }
    /**
     * Calculates the mode of a given set of numbers.
     * 
     * @param array $array Simple array of numbers.
     * @return float The Mode of the input array.
     */
    protected function mode(array $arr): float {
        $values = array();
        foreach ($arr as $v) {
          if (isset($values[$v])) {
            $values[$v] ++;
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
        if (count($modes)>1){
            //if there are an even number of items in the array the median function
            //will return an average, not an actual value in the array, so
            //make sure the array has an odd number of values
            if (!(count($modes)%2>0)){
                array_pop($modes);
            }
            return $this->median($modes);
        }else {
            //otherwise just get the single value and return that
            return $modes[0];
        }
          }
          
    /**
     * Returns a simple array with list of the names of all missingDataHandlerMethods.
     * 
     * @return array Simple array with string values representing names of missingDataHandlerMethods.
     */
    public function methodNames(): array{
        return $this->masterMethodList;
    }
    /**
     * Returns an object with metadata for all missingDataHandlerMethods.
     * 
     * @return object Object keys are strings corresponding to missingDataHandlerMethods, and values are arrays 
     *  containing 'formattedName', 'requiresInput' boolean, and 'description'.
     */
    public function methodsInfo(): array{
        return $this->masterMethodObject;
    }

    

    /**
     * Returns score to be used for a country with missing data, based on params.
     * 
     * @param array $existingScoresData A simple array containing all of the currently-calculated country scores, necessary input for most missingDataHandleMethods.
     * @param string $missingDataHandlerMethod Name of the method to use in this case @see $this->MasterMethodlist.
     * @param float $missingDataHandlerInput Optional. Input param required for some missingDataHandlerMethods.
     * @return int Result of calculations, to be used in place of a real score for a country.
     */
    public function getScoreNumeric(array $existingScoresData,string $missingDataHandlerMethod,?float $missingDataHandlerInput): int{
        asort($existingScoresData);//sorted smallest to largest
        $onePercentIndex = (count($existingScoresData)*1.0)/100.0;
        $inputParam = $missingDataHandlerInput;
        $resultValue;
        switch ($missingDataHandlerMethod){
            case 'average':
                $resultValue = array_sum($existingScoresData)/count($existingScoresData);
            break;
            case 'median':
                $resultValue = $this->median($existingScoresData);
            break;
            case 'mostFrequent':
                $resultValue = $this->mode($existingScoresData);
            break;
            case 'worseThanPercentage': //returned value will be worse than x% of the countries WITH data
                $closestIndex = round($onePercentIndex*$inputParam);
                $resultValue = $existingScoresData[count($existingScoresData)-$closestIndex-1]-1;
            break;
            case 'betterThanPercentage'://returned value will be better than x% of the countries WITH data
                $closestIndex = round($onePercentIndex*$inputParam);
                $resultValue = $existingScoresData[$closestIndex]+1;
            break;
            case 'specificScore':
                $resultValue = $inputParam;
            break;
            default:
            return die('error: missingdatahandlermethod not found');
        }
        return (int) $resultValue;
    }
}

