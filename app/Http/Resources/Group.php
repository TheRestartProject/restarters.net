<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class Group extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ret = [
            'id' => $this->idgroups,
            'name' => $this->name,
            'image' => $this->groupImage && is_object($this->groupImage) && is_object($this->groupImage->image) ? $this->groupImage->image->path : null,
            'area' => $this->area,
            'country' => $this->country,
            'website' => $this->website,
            'description' => $this->free_text,
            'stats' => $this->resource->getGroupStats(),
            'updated_at' => Carbon::parse($this->updated_at)->toIso8601String(),
            'location' => $this->location,
            'networks' => $this->resource->networks,
        ];

        $ret['hosts'] = $this->resource->all_confirmed_hosts_count;
        $ret['restarters'] = $this->resource->all_confirmed_restarters_count;

        // Get next approved event for group
        $nextevent = \App\Group::find($this->idgroups)->getNextUpcomingEvent();

        if ($nextevent) {
            // Using the resource for the nested event causes infinite loops.  Just add the model attributes we
            // need directly.
            $ret['next_event'] = [
                'id' => $nextevent->idevents,
                'start' => $nextevent->event_start_utc,
                'end' => $nextevent->event_end_utc,
                'timezone' => $nextevent->timezone,
                'title' => $nextevent->venue ?? $nextevent->location
            ];
        }

        return $ret;
    }
}
