<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Http\Resources\PartySummaryCollection;
use App\Party;
use Auth;
use Carbon\Carbon;
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

    public static function getGroupsByUsersNetworks(Request $request)
    {
        $authenticatedUser = Auth::user();

        $bbox = $minLat = $minLng = $maxLat = $maxLng = null;

        if ($request->has('bbox')) {
            $bbox = $request->get('bbox');
            if (preg_match('/(.*?),(.*?),(.*?),(.*)/', $bbox, $matches)) {
                $minLat = floatval($matches[1]);
                $minLng = floatval($matches[2]);
                $maxLat = floatval($matches[3]);
                $maxLng = floatval($matches[4]);
            }
        }

        $groups = [];

        foreach ($authenticatedUser->networks as $network) {
            foreach ($network->groups as $group) {
                $groups[] = $group;
            }
        }

        // New Collection Instance
        $collection = collect([]);

        foreach ($groups as $group) {
            // If we have a bounding box, check that the group is within it.
            if (! $bbox || (
                $group->latitude !== null && $group->longitude !== null &&
                $group->latitude >= $minLat && $group->latitude <= $maxLat &&
                $group->longitude >= $minLng && $group->longitude <= $maxLng
                )) {
                $groupStats = $group->getGroupStats();
                $collection->push([
                                      'id' => $group->idgroups,
                                      'name' => $group->name,
                                      'timezone' => $group->timezone,
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
                                        'volunteers' => $groupStats['participants'],
                                        'hours_volunteered' => $groupStats['hours_volunteered'],
                                          'parties_thrown' => $groupStats['parties'],
                                          'waste_prevented' => round($groupStats['waste_total']),
                                          'co2_emissions_prevented' => round($groupStats['co2_total']),
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
                                                           'event_date' => $event->event_date_local,
                                                           'start_time' => $event->start_local,
                                                           'end_time' => $event->end_local,
                                                           'timezone' => $event->timezone,
                                                           'name' => $event->venue,
                                                           'link' => $event->link,
                                                           'online' => $event->online,
                                                           'description' => $event->free_text,
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
                                                       'event_date' => $event->event_date_local,
                                                       'start_time' => $event->start_local,
                                                       'end_time' => $event->end_local,
                                                       'timezone' => $event->timezone,
                                                       'name' => $event->venue,
                                                       'link' => $event->link,
                                                       'online' => $event->online,
                                                       'description' => $event->free_text,
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
        // Used by old JS client.
        $group = $group->load('parties');

        $events = $group->parties->sortByDesc('event_start_utc');

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

    public static function getGroupv2(Request $request, $idgroups) {
        $group = Group::findOrFail($idgroups);
        return \App\Http\Resources\Group::make($group);
    }

    public static function getEventsForGroupv2(Request $request, $idgroups) {
        Group::findOrFail($idgroups);

        // Get date filters.  We default to far past and far future so that we don't need multiple code branches.  We
        // don't need to validate the date format - if they put junk in then they'll get junk matches back.
        $start = Carbon::parse($request->get('start', '1970-01-01'))->setTimezone('UTC')->toIso8601String();
        $end = Carbon::parse($request->get('end', '3000-01-01'))->setTimezone('UTC')->toIso8601String();

        $parties = Party::undeleted()->forGroup($idgroups)->where('event_start_utc', '>=', $start)->where('event_end_utc', '<=', $end)->get();

        return PartySummaryCollection::make($parties);
    }

    public function listVolunteers(Request $request, $idgroups) {
        $group = Group::findOrFail($idgroups);

        // Get the user that the API has been authenticated as.
        $user = auth('api')->user();

        if (!$user || !Fixometer::userHasEditGroupPermission($idgroups, $user->id)) {
            // We require host permissions to view the list of volunteers.  At the moment this call is only used when
            // adding volunteers, and this check means we don't have to worry about exposing sensitive data.
            abort(403);
        }

        $volunteers = $group->allConfirmedVolunteers()->get();

        $ret = [];

        foreach ($volunteers as $v) {
            $volunteer = $v->volunteer;
            $ret[] = [
                'id' => $volunteer->id,
                'name' => $volunteer->name,
                'email'=> $volunteer->email
            ];
        }

        return response()->json($ret);
    }
}
