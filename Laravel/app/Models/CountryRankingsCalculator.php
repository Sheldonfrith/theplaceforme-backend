<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

/**
 * Class for calculating country rankings and scores based on multiple dataset inputs. @see ScoresController
 * @link https://github.com/Sheldonfrith/theplaceforme-backend/wiki/Methodology
 * 
 * @method getFormattedScoresObject
 * 
 */
class CountryRankingsCalculator
{
   /**
    * Takes all the dataset configs as input (the input of the /scores POST request or a SavedScoresInput 'object') and 
    * returns a properly formatted output for the /scores POST or GET request.
    *
    * @global Country
    * @global ScoreCalculator
    * @param array $inputDatasets @see https://github.com/Sheldonfrith/theplaceforme-backend/wiki/API-Documentation /scores POST request body
    * @return array @see https://github.com/Sheldonfrith/theplaceforme-backend/wiki/API-Documentation /scores POST response body
    */
    public function getFormattedScoresObject($inputDatasets){
        $responseObject = [];//aka formattedScoresObject
        //populate the response object with each country and their names
        $countries = Country::select('alpha_three_code','primary_name')->where('alpha_three_code','!=',null)->get();
        foreach ($countries as $country){
            $code= $country['alpha_three_code'];
            $responseObject[$code]= [
                'primary_name'=>$country['primary_name'],
                'totalScore'=>0,
                'rank'=>0,
                'percentile'=>0,
                'categoryBreakdown'=>[],
                'scoreBreakdown'=>[]
            ];
        }
        //!CALCULATION, for each dataset
        foreach ($inputDatasets as $dataset){
            //get all the variables required for the calculation
            $id = $dataset['id'];
            $weight = $dataset['weight'];
            // if the weight is zero exit current dataset immediately without doing anything
            if ($weight == 0) continue;
            $idealValue = $dataset['idealValue'];
            $customScoreFunction = $dataset['customScoreFunction'];
            $missingDataHandlerMethod = $dataset['missingDataHandlerMethod'];
            $missingDataHandlerInput = array_key_exists('missingDataHandlerInput',$dataset)?$dataset['missingDataHandlerInput']:null;
            $normalizationPercentage = $dataset['normalizationPercentage'];
            //get the scores for this dataset
            $scoreCalculator = new ScoreCalculator(
                Dataset::where([['id','=',$dataset['id']],['id','!=',null]])->get()[0]->toArray(),
                $missingDataHandlerMethod,
                $missingDataHandlerInput,
                $weight,
                $idealValue,
                $customScoreFunction,
                $normalizationPercentage,
            );
            $scores = $scoreCalculator->getScoresObject();
            //push the scores for this dataset to the final object
            // Log::info($scores);
            foreach ($scores as $country=>$score){
                //set this data to the per-dataset score breakdown
                $responseObject[$country]['scoreBreakdown'][$id] = $score; //sets the 'score', 'rank', 'percentage', et.c all at once
                //update the category breakdowns
                $currentCategoryScore = false;
                if (isset($responseObject[$country]['categoryBreakdown'][$dataset['category']])){

                $currentCategoryScore = $responseObject[$country]['categoryBreakdown'][$dataset['category']];
                }
                if ($currentCategoryScore){
                    //its initialized, just add the new score to it
                    $responseObject[$country]['categoryBreakdown'][$dataset['category']] = $currentCategoryScore + $score['score'];
                }else {
                    //initialize it with this score
                    $responseObject[$country]['categoryBreakdown'][$dataset['category']] = $score['score'];
                }
                //add this data to the overall total score for each country
                // Log::info($score);
                $responseObject[$country]['totalScore'] += $score['score'];
            }
        }

        //get relative rankings for each country now that they all have final scores
        //first get a list with just the total scores, and country codes as keys
        $rankingArray ;
        foreach($countries as $country){
            $code = $country['alpha_three_code'];
            $rankingArray[$code] = $responseObject[$code]['totalScore'];
        }
        //sort that new list, highest scores are first on the list now
        arsort($rankingArray);
        $currentRank = 1;
        foreach($rankingArray as $country => $score){
        $responseObject[$country]['rank'] = $currentRank;
        //get the percentile also
        $percentile = 100-(($currentRank/(count($rankingArray)+1.0))*100.0);
        $responseObject[$country]['percentile'] = $percentile;
        $currentRank ++;
        }
        return $responseObject;
    }
}
