<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class Countries extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        
        return  [
            'id' => $this->id,
            'updated_at' => $this->updated_at,
            'alpha_three_code' => $this->alpha_three_code,
            'numeric_code' => $this->numeric_code,
            'primary_name' => $this->primary_name,
        ];
    }
}