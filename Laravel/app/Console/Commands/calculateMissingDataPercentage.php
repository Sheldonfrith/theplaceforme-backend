<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Country;
use App\Models\Dataset;

class calculateMissingDataPercentage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:calculate-missing-data-percentage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //get all datasets data
        $datasets = Dataset::all();
        $datasetsArrayForm = Dataset::all()->toArray();
        //for each dataset, calc the missing data percentage and push it to the database
        foreach ($datasetsArrayForm as $datasetKey => $dataset){
            //calculate missing data percentage here
            //get total number of countries
            $countries = Country::all()->toArray();
            $totalCountries = count($countries);
            $countriesWithMissingData = array_filter($dataset, function($value,$key){
                if ($value ===null){
                    if (strlen($key)<4 && $key !='id'){
                        return true;
                    }
                }
                return false;
            },ARRAY_FILTER_USE_BOTH);
            $totalCountriesMissingData = count($countriesWithMissingData);
            //calculate here
            $missingDataPercentage = $totalCountriesMissingData/($totalCountries/100.0);
            //save it here
            Dataset::where('id','=',$dataset['id'])->update(['missing_data_percentage'=>$missingDataPercentage]);
        }

        return 0;
    }
}
