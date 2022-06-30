<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Party extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // We return information which can be public, and we rename fields to look more consistent.
        return [
            'id' => $this->idevents,
            'start' => $this->event_start_utc,
            'end' => $this->event_end_utc,
            'timezone' => $this->timezone,
            'title' => $this->venue ?? $this->location,
            'location' => $this->location,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'group' => Group::make($this->theGroup),
            'description' => $this->free_text,
            // TODO Stats
        ];
    }
}
