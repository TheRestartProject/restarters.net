<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupSummary extends JsonResource
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
            'id' => $this->idgroups,
            'name' => $this->name,
            'image' => $this->groupImage && is_object($this->groupImage) && is_object($this->groupImage->image) ? $this->groupImage->image->path : null
        ];
    }
}
