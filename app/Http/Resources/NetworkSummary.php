<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NetworkSummary extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo && is_object($this->logo) && is_object($this->logo->image) ? $this->logo->image->path : null
        ];
    }
}
