<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(
 *     title="Event",
 *     schema="Event",
 *     description="The full information about an event.",
 *     required={"id", "full"},
 *     @OA\Property(
 *          property="id",
 *          title="id",
 *          description="Unique identifier of this event",
 *          format="int64",
 *          example=1
 *     ),
 *     @OA\Property(
 *          property="start",
 *          title="start",
 *          description="Start time of the event in ISO8601 format.",
 *          format="date-time",
 *          example="2022-09-18T11:30:00+00:00"
 *     ),
 *     @OA\Property(
 *          property="end",
 *          title="end",
 *          description="End time of the event in ISO8601 format",
 *          format="date-time",
 *          example="2022-09-18T12:30:00+00:00"
 *     ),
 *     @OA\Property(
 *          property="timezone",
 *          title="timezone",
 *          description="Timezone in which the event is taking place.",
 *          format="string",
 *          example="Europe/London"
 *     ),
 *     @OA\Property(
 *          property="title",
 *          title="title",
 *          description="Title of the event",
 *          format="string",
 *          example="Europe/London"
 *     ),
 *     @OA\Property(
 *          property="description",
 *          title="description",
 *          description="A description of the event.  May contain HTML",
 *          format="string",
 *          example="Come along and we'll fix your broken electrical items."
 *     ),
 *     @OA\Property(
 *          property="location",
 *          title="location",
 *          description="Human-readable address of the event",
 *          format="string",
 *          example="Europe/London"
 *     ),
 *     @OA\Property(
 *          property="lat",
 *          title="lat",
 *          description="Latitude at which the event is taking place.  Only valid if online=false.",
 *          format="float",
 *          example="50.8113243"
 *     ),
 *     @OA\Property(
 *          property="online",
 *          title="online",
 *          description="Whether this event is online (virtual).",
 *          format="boolean",
 *          example="false"
 *     ),
 *     @OA\Property(
 *          property="lng",
 *          title="lng",
 *          description="Longitude at which the event is taking place.  Only valid if online=false.",
 *          format="float",
 *          example="-1.0788839"
 *     ),
 *     @OA\Property(
 *          property="group",
 *          title="group",
 *          description="The group which is hosting this event.",
 *          ref="#/components/schemas/GroupSummary"
 *     ),
 *     @OA\Property(
 *         property="link",
 *         title="link",
 *         description="Optional web link",
 *         type="string",
 *     ),
 *     @OA\Property(
 *         property="approved",
 *         title="hosts",
 *         description="Whether the event has been approved",
 *         type="boolean",
 *     ),
 *     @OA\Property(
 *          description="Network-defined JSON data",
 *          property="network_data",
 *          @OA\Schema()
 *     ),
 *     @OA\Property(
 *          property="stats",
 *          title="stats",
 *          description="An array of statistics about the activity of an event.",
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
 *              description="The estimated number of hours volunteered for this event.",
 *              type="number",
 *          ),
 *     ),
 *     @OA\Property(
 *          property="updated_at",
 *          title="updated_at",
 *          description="The last change to this group.  This includes changes which affect the stats.",
 *          format="date-time",
 *     ),
 *     @OA\Property(
 *          property="attending",
 *          title="attending",
 *          description="Whether the authenticated user is attending this event (EventsUsers.status==='1'). Omitted when the caller is not authenticated.",
 *          format="boolean",
 *          example="true",
 *          nullable=true
 *     ),
 *     @OA\Property(
 *         property="images",
 *         title="images",
 *         description="Any photos uploaded for this event",
 *         type="array",
 *         @OA\Items(
 *           ref="#/components/schemas/Image"
 *         )
 *     ),
 *     @OA\Property(
 *          property="discourse_thread",
 *          title="discourse_thread",
 *          description="The id of this event's linked Discourse discussion thread. Combine with the session config's discourse_url as {discourse_url}/t/{discourse_thread} to link to the thread. Only present when the authenticated user is a confirmed attendee (EventsUsers.status==='1', same check as 'attending') - unlike Group.discourse_group this is not public. Null when there is no linked thread, the caller is unauthenticated, or the caller isn't a confirmed attendee.",
 *          format="string",
 *          nullable=true,
 *          example="4821"
 *     ),
 *     @OA\Property(
 *          property="full",
 *          title="full",
 *          description="Indicates that this is a full result, not summary group information.",
 *          format="boolean",
 *          example="true"
 *     )
 * )
 */

class Party extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // We return information which can be public, and we rename fields to look more consistent.
        $networkData = gettype($this->network_data) == 'string' ? json_decode($this->network_data, true) : $this->network_data;

        if (getType($networkData) === 'array' && !count(array_keys($networkData))) {
            // We don't want to return [] as the client expects an object, not an array.
            $networkData = null;
        }

        $ret = [
            'id' => $this->idevents,
            'start' => $this->event_start_utc,
            'end' => $this->event_end_utc,
            'timezone' => $this->timezone,
            'title' => $this->venue ?? $this->location,
            'location' => $this->location,
            'online' => $this->online,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'group' => GroupSummary::make($this->theGroup),
            'description' => $this->free_text,
            'stats' => $this->resource->getEventStats(),
            'updated_at' => Carbon::parse($this->updated_at)->toIso8601String(),
            'approved' => $this->approved ? true : false,
            'network_data' => $networkData,
            'full' => true,
            'images' => \App\Http\Resources\Image::collection($this->resource->getImages()),
            // develop's GroupEventScrollTable shows an invited-volunteer count
            // (PartyController.php:76's allinvitedcount), which the moderation
            // queue needs. whenCounted, NOT a direct ->count(): this resource is
            // used by list endpoints, and counting per row there would be an
            // N+1. Endpoints that want it call loadCount('allInvited') - one
            // query for the whole collection - and it is simply absent
            // elsewhere, exactly as before.
            'invited' => $this->whenCounted('allInvited'),
        ];

        // Mirrors expandEvent()'s Auth::user() && $event->isBeingAttendedBy(...) check (strict
        // status==='1'). Optional-auth chain matches Volunteer resource / API\EventController::getUser().
        $currentUser = Auth::user() ?? auth('sanctum')->user() ?? auth('api')->user();
        $isAttending = $currentUser !== null && $this->resource->isBeingAttendedBy($currentUser->id);
        $ret['attending'] = $this->when($currentUser !== null, fn () => $isAttending);

        // events/view.blade.php: $discourseThread = ($is_attending && $event->discourse_thread) ?
        // ... : null - gated the same way as 'attending' above, unlike Group.discourse_group which
        // is unconditionally public.
        $ret['discourse_thread'] = ($isAttending && $this->discourse_thread) ? $this->discourse_thread : null;

        if ($this->link) {
            // Don't return this unless present - the OpenAPI schema doesn't allow null values.
            $ret['link'] = $this->link;
        }

        return $ret;
    }
}
