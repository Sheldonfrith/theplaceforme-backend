<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dataset;

class calculateDistributionMaps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:calc-distribution-maps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate all distribution maps for datasets in the database';

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
        //for each dataset, calc the distirbution map and push it to the database
        foreach ($datasetsArrayForm as $datasetKey => $dataset){
            //calculate distribution map here
            //setup variables for the calculation
            $min = $dataset['min_value'];
            $max = $dataset['max_value'];
            //populate the distribution map with zeros, goes from 0% to 100%
            $distributionMap = array_fill(0,101,0);
            //get a list of just the countrys with non-null values
            $deNullified = array_filter($dataset, function($value,$key){
                if ($value !== null){
                    if (strlen($key)<4 && $key != 'id'){
                        return true;
                    }
                }
                return false;
            },ARRAY_FILTER_USE_BOTH);
            //go through each country and put its count in the appropriate place in the distribution map
            foreach ($deNullified as $value){
                $index;
                $index = (int) round(($value-$min)*100.0/($max-$min)); //index is the percentage of the value relative to the dataset range rounded to nearest integer
                $distributionMap[$index] ++;
            }
            //save it here
            Dataset::where('id','=',$dataset['id'])->update(['distribution_map'=>$distributionMap]);
        }

        return 0;
    }
}
