<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MissingDataHandlers extends JsonResource
{
    public $preserveKeys = true;
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
                'formattedName'=>$this['method_name_formatted'],
                'requiresInput'=>(bool) $this['requires_input'],
                'description'=>$this['description'],
        ];
    }
}
