<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Dataset;
use Illuminate\Support\Facades\Log;

class DatasetSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $dataset;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Dataset $dataset)
    {
        $this->dataset = $dataset;
        Log::info('constructing DatasetSubmitted event');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
