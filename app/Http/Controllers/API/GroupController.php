<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * List changes made to groups.
     * Makes use of the audits produced by Laravel audits.
     *
     * Created specifically for use as a Zapier trigger.
     *
     * Only Administrators can access this API call.
     */
    public static function getGroupChanges(Request $request)
    {
        $authenticatedUser = Auth::user();
        if (! $authenticatedUser->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $dateFrom = $request->input('date_from', null);

        $groupAudits = self::getGroupAudits($dateFrom);

        $groupChanges = [];
        foreach ($groupAudits as $groupAudit) {
            $group = Group::find($groupAudit->auditable_id);
            if (! is_null($group) && $group->changesShouldPushToZapier()) {
                $groupChanges[] = self::mapDetailsAndAuditToChange($group, $groupAudit);
            }
        }

        return response()->json($groupChanges);
    }

    /**
     * To provide a more uniform API, this is just a wrapper around
     * the method in the GroupController for now.
     *
     * That method should be moved out of the controller.
     */
    public static function getGroupsByUserGroupTag(Request $request)
    {
        $authenticatedUser = Auth::user();

        $groupController = new \App\Http\Controllers\GroupController();

        $groups = $groupController->getGroupsByKey($request, $authenticatedUser->api_token);

        return response()->json($groups);
    }

    public static function getGroupsByUsersNetworks(Request $request)
    {
        $authenticatedUser = Auth::user();

        $groups = [];

        foreach ($authenticatedUser->networks as $network) {
            foreach ($network->groups as $group) {
                $groups[] = $group;
            }
        }

        // New Collection Instance
        $collection = collect([]);

        foreach ($groups as $group) {
            $groupStats = $group->getGroupStats();
            $collection->push([
                'id' => $group->idgroups,
                'name' => $group->name,
                'location' => [
                    'value' => $group->location,
                    'country' => $group->country,
                    'latitude' => $group->latitude,
                    'longitude' => $group->longitude,
                    'area' => $group->area,
                    'postcode' => $group->postcode,
                ],
                'website' => $group->website,
                'facebook' => $group->facebook,
                'description' => $group->free_text,
                'image_url' => $group->groupImagePath(),
                'upcoming_parties' => $upcoming_parties_collection = collect([]),
                'past_parties' => $past_parties_collection = collect([]),
                'impact' => [
                    'volunteers' => $groupStats['pax'],
                    'hours_volunteered' => $groupStats['hours'],
                    'parties_thrown' => $groupStats['parties'],
                    'waste_prevented' => $groupStats['waste'],
                    'co2_emissions_prevented' => $groupStats['co2'],
                ],
                'widgets' => [
                    'headline_stats' => url("/group/stats/{$group->idgroups}"),
                    'co2_equivalence_visualisation' => url("/outbound/info/group/{$group->idgroups}/manufacture"),
                ],
                'created_at' => new \Carbon\Carbon($group->created_at),
                'updated_at' => new \Carbon\Carbon($group->max_updated_at_devices_updated_at),

              ]);

            foreach ($group->upcomingParties() as $event) {
                $upcoming_parties_collection->push([
                    'event_id' => $event->idevents,
                    'event_date' => $event->event_date,
                    'start_time' => $event->start,
                    'end_time' => $event->end,
                    'name' => $event->venue,
                    'location' => [
                        'value' => $event->location,
                        'latitude' => $event->latitude,
                        'longitude' => $event->longitude,
                    ],
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                ]);
            }

            foreach ($group->pastParties() as $key => $event) {
                $past_parties_collection->push([
                    'event_id' => $event->idevents,
                    'event_date' => $event->event_date,
                    'start_time' => $event->start,
                    'end_time' => $event->end,
                    'name' => $event->venue,
                    'location' => [
                        'value' => $event->location,
                        'latitude' => $event->latitude,
                        'longitude' => $event->longitude,
                    ],
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                ]);
            }
        }

        return response()->json($collection);
    }

    /**
     * Get all of the audits related to groups from the audits table.
     */
    public static function getGroupAudits($dateFrom = null)
    {
        $query = \OwenIt\Auditing\Models\Audit::where('auditable_type', \App\Group::class);

        if (! is_null($dateFrom)) {
            $query->where('created_at', '>=', $dateFrom);
        }

        $query->groupBy('created_at')
              ->orderBy('created_at', 'desc');

        return $query->get();
    }

    /**
     * Map from the group and audit information as recorded by the audits library,
     * into the format needed for Zapier.
     */
    public static function mapDetailsAndAuditToChange($group, $groupAudit)
    {
        $group->makeHidden(['updated_at', 'wordpress_post_id', 'ShareableLink', 'shareable_code']);
        $groupChange = $group->toArray();

        // Zapier makes use of this unique hash as an id for the change for deduplication.
        $auditCreatedAtAsString = $groupAudit->created_at->toDateTimeString();
        $groupChange['id'] = md5($group->idgroups.$auditCreatedAtAsString);
        $groupChange['group_id'] = $group->idgroups;
        $groupChange['change_occurred_at'] = $auditCreatedAtAsString;
        $groupChange['change_type'] = $groupAudit->event;

        return $groupChange;
    }

    public static function getGroupList()
    {
        $groups = Group::orderBy('created_at', 'desc');

        $groups = $groups->get();
        foreach ($groups as $group) {
            mb_convert_encoding($group, 'UTF-8', 'UTF-8');
        }

        return response()->json($groups);
    }

    public static function getEventsForGroup(Request $request, Group $group)
    {
        $group = $group->load('parties');

        $events = $group->parties->sortByDesc('event_date');

        if ($request->has('format') && $request->input('format') == 'location') {
            $events = $events->map(function ($event) {
                return (object) [
                    'id' => $event->idevents,
                    'location' => $event->FriendlyLocation,
                ];
            });
        }

        return response()->json([
            'events' => $events->values()->toJson(),
        ]);
    }
}
