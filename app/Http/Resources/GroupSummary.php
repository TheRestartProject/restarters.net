<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Cache;

/**
 * @OA\Schema(
 *     title="GroupSummary",
 *     schema="GroupSummary",
 *     description="The very basic information about a group.  For full information, fetch the group.",
 *     required={"id", "summary"},
 *     @OA\Property(
 *          property="id",
 *          title="id",
 *          description="Unique identifier of this group",
 *          format="int64",
 *          example=1
 *     ),
 *     @OA\Property(
 *          property="name",
 *          title="name",
 *          description="Unique name of this group",
 *          format="string",
 *          example="Restarters HQ"
 *     ),
 *     @OA\Property(
 *          property="image",
 *          title="image",
 *          description="URL of an image for this group.  You should prefix this with /uploads before use.",
 *          format="string",
 *          example="/mid_1597853610178a4b76e4d666b2a7b32ee75d8a24c706f1cbf213970.png"
 *     ),
 *     @OA\Property(
 *          property="location",
 *          title="location",
 *          description="The group's location",
 *          format="object",
 *          ref="#/components/schemas/GroupLocation"
 *     ),
 *     @OA\Property(
 *         property="networks",
 *         title="networks",
 *         description="An array of networks of which the group is a member.",
 *         type="array",
 *         @OA\Items(
 *            ref="#/components/schemas/NetworkSummary"
 *         )
 *     ),
 *     @OA\Property(
 *          property="updated_at",
 *          title="updated_at",
 *          description="The last change to this group.  This includes changes which affect the stats.",
 *          format="date-time",
 *     ),
 *     @OA\Property(
 *          property="next_event",
 *          title="next_event",
 *          description="The next event, if any, for this group.  Only present if includeNextEvent=true.",
 *          format="object",
 *          ref="#/components/schemas/EventSummary"
 *     ),
 *     @OA\Property(
 *         property="hosts",
 *         title="hosts",
 *         description="The number of hosts of this group (if requested via API call flag).",
 *         type="number",
 *     ),
 *     @OA\Property(
 *         property="restarters",
 *         title="hosts",
 *         description="The number of restarters in this group (if requested via API call flag).",
 *         type="number",
 *     ),
 *     @OA\Property(
 *          property="summary",
 *          title="summary",
 *          description="Indicates that this is a summary result, not full group information.",
 *          format="boolean",
 *          example="true"
 *     ),
 *     @OA\Property(
 *          property="archived_at",
 *          title="archived_at",
 *          description="If present, this group has been archived and is no longer active.",
 *          format="date-time",
 *     ),
 * )
 */

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
        $ret = [
            'id' => $this->idgroups,
            'name' => $this->name,
            'image' => $this->groupImage && is_object($this->groupImage) && is_object($this->groupImage->image) ? $this->groupImage->image->path : null,
            'location' => new GroupLocation($this),
            'networks' => new NetworkSummaryCollection($this->resource->networks),
            'updated_at' => Carbon::parse($this->updated_at)->toIso8601String(),
            'archived_at' => $this->archived_at ? Carbon::parse($this->archived_at)->toIso8601String() : null,
            'summary' => true
        ];

        if ($request->get('includeCounts', false)) {
            $ret['hosts'] = $this->resource->all_confirmed_hosts_count;
            $ret['restarters'] = $this->resource->all_confirmed_restarters_count;
        }

        if ($request->get('includeNextEvent', false)) {
            // Get next approved event for group.  We cache all upcoming events to speed up the case where we
            // are fetching many groups.
            if (Cache::has('future_events')) {
                $upcoming = Cache::get('future_events');
            } else {
                $future = \App\Party::future()->get();

                // Can't serialise the whole event, and we only need a few fields.
                $upcoming = [];

                foreach ($future as $event) {
                    $upcoming[] = [
                        'id' => $event->idevents,
                        'group_id' => $event->group,
                        'start' => $event->event_start_utc,
                        'end' => $event->event_end_utc,
                        'timezone' => $event->timezone,
                        'title' => $event->venue ?? $event->location,
                        'location' => $event->location,
                        'online' => $event->online,
                        'lat' => $event->latitude,
                        'lng' => $event->longitude,
                        'updated_at' => $event->updated_at->toIso8601String(),
                        'summary' => true
                    ];
                }

                Cache::put('future_events', $upcoming, 60);
            }

            // Find the next event for this group.
            $nextevent = null;

            foreach ($upcoming as $event) {
                if ($event['group_id'] == $this->idgroups) {
                    $nextevent = $event;
                    break;
                }
            }

           $ret['next_event'] = $nextevent;
        }

        return($ret);
    }
}
