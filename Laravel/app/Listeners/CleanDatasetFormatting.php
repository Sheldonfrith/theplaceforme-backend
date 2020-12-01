<?php

namespace App\Listeners;

use App\Events\DatasetSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CleanDatasetFormatting
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
    }
}
