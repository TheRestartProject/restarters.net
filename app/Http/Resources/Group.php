<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Group",
 *     schema="Group",
 *     description="A Group of Restarters, who organise events.",
 *     required={"id", "full"},
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
 *          property="location",
 *          title="location",
 *          description="The group's location",
 *          format="object",
 *          ref="#/components/schemas/GroupLocation"
 *     ),
 *     @OA\Property(
 *          property="image",
 *          title="image",
 *          description="URL of an image for this group.  You should prefix this with /uploads before use.",
 *          format="string",
 *          example="/mid_1597853610178a4b76e4d666b2a7b32ee75d8a24c706f1cbf213970.png"
 *     ),
 *     @OA\Property(
 *          property="phone",
 *          title="phone",
 *          description="An optional phone number to contact the group.",
 *          format="string",
 *          example="07544 314678"
 *     ),
 *     @OA\Property(
 *          property="website",
 *          title="website",
 *          description="An URL for the group's own separate website.",
 *          format="string",
 *          example="https://therestartproject.org"
 *     ),
 *     @OA\Property(
 *          property="description",
 *          title="description",
 *          description="HTML description of the group.",
 *          format="string",
 *          example="<p>This is a description.</p>"
 *     ),
 *     @OA\Property(
 *          property="next_event",
 *          title="next_event",
 *          description="Next event for this group",
 *          ref="#/components/schemas/Event"
 *     ),
 *     @OA\Property(
 *          property="timezone",
 *          title="timezone",
 *          description="Timezone for this group.  If empty will inherit the timezone from the network.",
 *          format="string",
 *          example="Europe/London"
 *     ),
 *     @OA\Property(
 *         property="hosts",
 *         title="hosts",
 *         description="The number of hosts of this group.",
 *         type="number",
 *     ),
 *     @OA\Property(
 *         property="restarters",
 *         title="hosts",
 *         description="The number of restarters in this group.",
 *         type="number",
 *     ),
 *     @OA\Property(
 *         property="approved",
 *         title="hosts",
 *         description="Whether the group has been approved",
 *         type="boolean",
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
 *         property="tags",
 *         title="tags",
 *         description="An array of tags which apply to the group.",
 *         type="array",
 *         @OA\Items(
 *            ref="#/components/schemas/Tag"
 *         )
 *     ),
 *     @OA\Property(
 *          property="full",
 *          title="full",
 *          description="Indicates that this is a full result, not summary group information.",
 *          format="boolean",
 *          example="true"
 *     ),
 *     @OA\Property(
 *          property="stats",
 *          title="stats",
 *          description="An array of statistics about the activity of a group.",
 *          format="object",
 *          @OA\Property(
 *              property="co2_powered",
 *              title="co2_powered",
 *              description="The amount of CO2 saved by repairing powered items.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="co2_unpowered",
 *              title="co2_unpowered",
 *              description="The amount of CO2 saved by repairing unpowered items.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="co2_total",
 *              title="co2_total",
 *              description="co2_powered + co2_unpowered",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="waste_powered",
 *              title="waste_powered",
 *              description="The weight in kg of waste saved by repairing powered items.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="waste_unpowered",
 *              title="waste_unpowered",
 *              description="The weight in kg of waste saved by repairing unpowered items.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="fixed_powered",
 *              title="fixed_powered",
 *              description="The number of powered items which have been repaired.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="fixed_unpowered",
 *              title="fixed_unpowered",
 *              description="The number of unpowered items which have been repaired.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="fixed_devices",
 *              title="fixed_devices",
 *              description="fixed_powered + fixed_unpowered",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="repairable_devices",
 *              title="repairable_devices",
 *              description="The number of devices which were capable of being repaired.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="dead_devices",
 *              title="dead_devices",
 *              description="The number of devices which were not capable of being repaired.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="unknown_repair_status",
 *              title="unknown_repair_status",
 *              description="The number of devices where the repair status was not known.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="devices_powered",
 *              title="devices_powered",
 *              description="The number of powered devices seen.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="devices_unpowered",
 *              title="devices_unpowered",
 *              description="The number of unpowered devices seen.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="no_weight_powered",
 *              title="no_weight_powered",
 *              description="The number of powered devices where no weight was provided.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="no_weight_unpowered",
 *              title="no_weight_unpowered",
 *              description="The number of unpowered devices where no weight was provided.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="participants",
 *              title="participants",
 *              description="The number of people who attended.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="volunteers",
 *              title="volunteers",
 *              description="The number of volunteers.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="hours_volunteered",
 *              title="hours_volunteered",
 *              description="The estimated number of hours volunteered for this group.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="events",
 *              title="events",
 *              description="The number of events created by this group.",
 *              type="number",
 *          ),
 *     ),
 *     @OA\Property(
 *          property="updated_at",
 *          title="updated_at",
 *          description="The last change to this group.  This includes changes which affect the stats.",
 *          format="date-time",
 *     )
 * )
*/
class Group extends JsonResource
{
    private $data;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $stats = $this->resource->getGroupStats();
        $stats['events'] = $stats['parties'];
        unset($stats['parties']);

        $ret = [
            'id' => $this->idgroups,
            'name' => $this->name,
            'image' => $this->groupImage && is_object($this->groupImage) && is_object($this->groupImage->image) ? $this->groupImage->image->path : null,
            'website' => $this->website,
            'phone' => $this->phone,
            'description' => $this->free_text,
            'stats' => $stats,
            'updated_at' => Carbon::parse($this->updated_at)->toIso8601String(),
            'location' => new GroupLocation($this),
            'networks' => new NetworkSummaryCollection($this->networks),
            'tags' => new TagCollection($this->group_tags),
            'timezone' => $this->timezone,
            'approved' => $this->approved ? true : false,
            'full' => true
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
