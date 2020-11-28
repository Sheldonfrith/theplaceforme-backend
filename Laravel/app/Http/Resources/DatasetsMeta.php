<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DatasetsMeta extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'updated_at'=>$this->updated_at,
            'long_name'=>$this->long_name,
            'data_type'=>$this->data_type,
            'max_value'=>$this->max_value,
            'min_value'=>$this->min_value,
            'source_link'=>$this->source_link,
            'source_description'=>$this->source_description,
            'unit_description'=>$this->unit_description,
            'notes'=>$this->notes,
            'category'=>$this->category,
            'distribution_map'=>$this->distribution_map,
            'missing_data_percentage'=>$this->missing_data_percentage,
        ];
    }
}
