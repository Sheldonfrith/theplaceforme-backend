<?php

namespace App\Listeners;

use App\Events\DatasetSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Dataset;

class UpdateDatasetCalculatedFields
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  DatasetSubmitted  $event
     * @return void
     */
    public function handle(DatasetSubmitted $event)
    {
        $datasetID = $event->datasetID;
        $dataset = Dataset::find($datasetID);
        $deNullified = array_diff($dataset->getWithoutAnyMetadata(), array(null));
        [$min, $max] = $this->getMinAndMaxValues($deNullified,$dataset->data_type);
        $dataset->min_value = $min;
        $dataset->max_value = $max;
        $dataset->distribution_map =  $this->getDistributionMap($deNullified, $min, $max);
        $dataset->save();
    }
    protected function getMinAndMaxValues($dataset, $data_type){
        if ($data_type==='float' || $data_type==='double' || $data_type==='integer'){
            $min = min($dataset);
            $max = max($dataset);
        } elseif($data_type==='boolean') {
            $min = false;
            $max = true;
        } else {
            $min = null;
            $max = null;
        }
        return [$min, $max];
    }
    protected function getDistributionMap($deNullified, $min, $max){
        $distributionMap = array_fill(0,101,0);
        foreach ($deNullified as $currentVal){
            $index = null;
            $index = (int) round(($currentVal-$min)*100.0/($max-$min)); //index is the percentage of the value relative to the dataset range rounded to nearest integer
            $distributionMap[$index] ++;
        } 
    }
}
