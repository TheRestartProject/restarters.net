<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PartySummary extends JsonResource
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
        //
        // The updated_at field is always being returned NULL, even though it's set.  This will be some Eloquent
        // peculiarity which I can't get to the bottom of.  So pull it from the resource.
        return [
            'id' => $this->idevents,
            'start' => $this->event_start_utc,
            'end' => $this->event_end_utc,
            'timezone' => $this->timezone,
            'title' => $this->venue ?? $this->location,
            'location' => $this->location,
            'online' => $this->online,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'group' => \App\Http\Resources\GroupSummary::make($this->theGroup),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
