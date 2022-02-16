<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Http\Controllers\Controller;
use App\Party;
use Auth;
use Illuminate\Http\Request;

class EventController extends Controller
{
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
            // TODO Timezones.  Add optional timezone parameter to route, defaulted to UTC, and let API users
            // know about.
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
               'participants' => $groupStats['participants'],
               'hours_volunteered' => $groupStats['hours_volunteered'],
               'parties_thrown' => $groupStats['parties'],
               'waste_prevented' => $groupStats['waste_total'],
               'co2_emissions_prevented' => $groupStats['co2_total'],
           ]);
        }

        $collection = collect([]);

        // Send these to getEventStats() to speed things up a bit.
        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

        foreach ($parties as $key => $party) {
            $group = $groups_array->filter(function ($group) use ($party) {
                return $group['id'] == $party->group;
            })->first();

            $eventStats = $party->getEventStats($eEmissionRatio, $uEmissionratio);
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
// TODO Once DOT-1502 is released 'link' => $party->link,
             'online' => $party->online,
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
                 'waste_prevented' => $eventStats['waste_powered'],
                 'co2_emissions_prevented' => $eventStats['co2_powered'],
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
