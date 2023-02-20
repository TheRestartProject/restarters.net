<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="EventSummary",
 *     schema="EventSummary",
 *     description="The basic information about an event.  For full information, fetch the event.",
 *     @OA\Xml(
 *         name="EventSummary"
 *     ),
 * )
 */

class PartySummary extends JsonResource
{
    /**
     *     @OA\Property(
     *          property="id",
     *          title="id",
     *          description="Unique identifier of this event",
     *          format="int64",
     *          example=1
     *     )
     *     @OA\Property(
     *          property="start",
     *          title="start",
     *          description="Start time of the event in ISO8601 format.",
     *          format="date-time",
     *          example="2022-09-18T11:30:00+00:00"
     *     )
     *     @OA\Property(
     *          property="end",
     *          title="end",
     *          description="End time of the event in ISO8601 format",
     *          format="date-time",
     *          example="2022-09-18T12:30:00+00:00"
     *     )
     *     @OA\Property(
     *          property="timezone",
     *          title="timezone",
     *          description="Timezone in which the event is taking place.",
     *          format="string",
     *          example="Europe/London"
     *     )
     *     @OA\Property(
     *          property="title",
     *          title="title",
     *          description="Title of the event",
     *          format="string",
     *          example="Europe/London"
     *     )
     *     @OA\Property(
     *          property="location",
     *          title="location",
     *          description="Human-readable address of the event",
     *          format="string",
     *          example="Europe/London"
     *     )
     *     @OA\Property(
     *          property="online",
     *          title="online",
     *          description="Whether this event is online (virtual).",
     *          format="boolean",
     *          example="false"
     *     )
     *     @OA\Property(
     *          property="lat",
     *          title="lat",
     *          description="Latitude at which the event is taking place.  Only valid if online=false.",
     *          format="float",
     *          example="50.8113243"
     *     )
     *     @OA\Property(
     *          property="lng",
     *          title="lng",
     *          description="Longitude at which the event is taking place.  Only valid if online=false.",
     *          format="float",
     *          example="-1.0788839"
     *     )
     *     @OA\Property(
     *          property="group",
     *          title="group",
     *          description="The group which is hosting this event.",
     *          ref="#/components/schemas/GroupSummary"
     *     )
     *     @OA\Property(
     *          property="updated_at",
     *          title="updated_at",
     *          description="The last change to this group.  This includes changes which affect the stats.",
     *          format="date-time",
     *     )
     */

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
            'updated_at' => $this->updated_at->toIso8601String()
        ];
    }
}
