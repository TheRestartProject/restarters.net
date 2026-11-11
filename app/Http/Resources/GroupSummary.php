<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
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
 *         property="group_tags_full",
 *         title="group_tags_full",
 *         description="Tags on this group.  Only present on calls which load them, e.g. the summary list.",
 *         type="array",
 *         @OA\Items(
 *            @OA\Property(property="id", type="integer"),
 *            @OA\Property(property="name", type="string"),
 *            @OA\Property(property="network_id", type="integer", nullable=true),
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
     */
    public function toArray(Request $request): array
    {
        $ret = [
            'id' => $this->idgroups,
            'name' => $this->name,
            'image' => $this->groupImage && is_object($this->groupImage) && is_object($this->groupImage->image) ? $this->groupImage->image->path : null,
            'location' => new GroupLocation($this),
            'networks' => new NetworkSummaryCollection($this->resource->networks),
            // Tags drive the badges and the tag filter on the groups list.
            // Only included when the caller eager-loaded them, so other users
            // of this resource don't pick up an N+1.
            'group_tags_full' => $this->whenLoaded('group_tags', function () {
                return $this->resource->group_tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->tag_name,
                        'network_id' => $tag->network_id,
                    ];
                });
            }),
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
            //
            // Only approved events count, matching Group::getNextUpcomingEvent() which the group's own page uses.
            // Without this an event still awaiting moderation would be advertised as the group's next event on
            // the public map while the group's page ignored it.
            $ret['next_event'] = self::nextEvents($request)[$this->idgroups] ?? null;
        }

        return($ret);
    }

    /**
     * The next approved event of every group that has one, by group id. Worked out once per request, rather than
     * once per group in the list.
     */
    private static function nextEvents($request): array
    {
        $next = $request->attributes->get('group_summary_next_events');

        if ($next === null) {
            $upcoming = Cache::remember('future_approved_events', 60, function () {
                // Can't serialise the whole event, and we only need a few fields.
                return \App\Party::future()->where('approved', true)->get()->map(fn ($event) => [
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
                ])->all();
            });

            // The first of a group's events is its next one.
            $next = [];

            foreach ($upcoming as $event) {
                $next[$event['group_id']] ??= $event;
            }

            $request->attributes->set('group_summary_next_events', $next);
        }

        return $next;
    }
}
