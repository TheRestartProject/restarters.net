<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Helpers\FootprintRatioCalculator;
use App\Http\Controllers\Controller;
use App\Party;
use Auth;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /** ToDo Test */
    public function getEventsByUsersNetworks(Request $request, $date_from = null, $date_to = null)
    {
        $authenticatedUser = Auth::user();

        $groups = [];
        foreach ($authenticatedUser->networks as $network) {
            foreach ($network->groups as $group) {
                $groups[] = $group;
            }
        }
        $parties = Party::join('groups', 'groups.idgroups', '=', 'events.group')
                  ->join('group_network', 'group_network.group_id', '=', 'groups.idgroups')
                  ->join('networks', 'networks.id', '=', 'group_network.network_id')
                  ->join('user_network', 'networks.id', '=', 'user_network.network_id')
                  ->join('users', 'users.id', '=', 'user_network.user_id');

        if (! empty($date_from) && ! empty($date_to)) {
            $parties = $parties->where('events.event_date', '>=', date('Y-m-d', strtotime($date_from)))
           ->where('events.event_date', '<=', date('Y-m-d', strtotime($date_to)));
        }

        $parties = $parties->where([
             ['users.api_token', $authenticatedUser->api_token],
         ])
         ->select('events.*')
         ->get();

        // If no parties are found, through 404 error
        if (empty($parties)) {
            return abort(404, 'No Events found.');
        }

        $groups_array = collect([]);
        foreach ($groups as $group) {
            $groupStats = $group->getGroupStats();
            $groups_array->push([
               'id' => $group->idgroups,
               'name' => $group->name,
               'area' => $group->area,
               'postcode' => $group->postcode,
               'description' => $group->free_text,
               'image_url' => $group->groupImagePath(),
               'volunteers' => $group->volunteers,
               'participants' => $groupStats['pax'],
               'hours_volunteered' => $groupStats['hours'],
               'parties_thrown' => $groupStats['parties'],
               'waste_prevented' => $groupStats['waste'],
               'co2_emissions_prevented' => $groupStats['co2'],
           ]);
        }

        $collection = collect([]);
        foreach ($parties as $key => $party) {
            $group = $groups_array->filter(function ($group) use ($party) {
                return $group['id'] == $party->group;
            })->first();

            $eventStats = $party->getEventStats();
            // Push Party to Collection
            $collection->push([
             'id' => $party->idevents,
             'group' => [$group],
             'area' => $group['area'],
             'postcode' => $group['postcode'],
             'event_date' => $party->event_date,
             'start_time' => $party->start,
             'end_time' => $party->end,
             'name' => $party->venue,
             'location' => [
                 'value' => $party->location,
                 'latitude' => $party->latitude,
                 'longitude' => $party->longitude,
                 'area' => $group['area'],
                 'postcode' => $group['postcode'],
             ],
             'description' => $party->free_text,
             'user' => $party_user = collect(),
             'impact' => [
                 'participants' => $party->pax,
                 'volunteers' => $eventStats['volunteers'],
                 'waste_prevented' => $eventStats['ewaste'],
                 'co2_emissions_prevented' => $eventStats['co2'],
                 'devices_fixed' => $eventStats['fixed_devices'],
                 'devices_repairable' => $eventStats['repairable_devices'],
                 'devices_dead' => $eventStats['dead_devices'],
             ],
             'widgets' => [
                 'headline_stats' => url("/party/stats/{$party->idevents}/wide"),
                 'co2_equivalence_visualisation' => url("/outbound/info/party/{$party->idevents}/manufacture"),
             ],
             'hours_volunteered' => $party->hoursVolunteered(),
             'created_at' => new \Carbon\Carbon($party->created_at),
             'updated_at' => new \Carbon\Carbon($party->max_updated_at_devices_updated_at),
           ]);

            if (! empty($party->owner)) {
                $party_user->put('id', $party->owner->id);
                $party_user->put('name', $party->owner->name);
            }
        }

        return $collection;
    }
}
