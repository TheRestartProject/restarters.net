<?php

namespace App\Helpers;

use App\Group;
use App\GroupTags;
use App\Helpers\Fixometer;
use App\Party;
use App\User;
use Auth;
use DateTime;
use Illuminate\Http\Request;

class SearchHelper
{

    public static function getDefaultViewParams()
    {
        return [
            'title' => 'Filter Stats',
            'charts' => true,
            'clusters' => null,
            'mostleast' => null,
            'groups' => [],
            'sorted_parties' => [],
            'stats' => null,
            'response' => null,
            'user' => User::find(Auth::id()),
            'group_tags' => GroupTags::all(),
        ];
    }

    public static function getUserGroupsAndParties()
    {
        /** Init all needed classes **/
        $Groups = new Group;
        $Parties = new Party;
        $user = User::find(Auth::id());
        $data = [
            'groups' => [],
            'group_ids' => [],
            'parties' => [],
            'sorted_parties' => [],
            'allowed_parties' => [],
        ];

        /* Get default data for the search dropdowns **/
        if (Fixometer::hasRole($user, 'Administrator')) {
            $data['groups'] = $Groups->findList();
            $data['parties'] = $Parties->findAllSearchable();
            foreach ($data['parties'] as $i => $party) {
                $data['parties'][$i]->venue = is_null($data['parties'][$i]->venue) ? $data['parties'][$i]->location : $data['parties'][$i]->venue;
                $data['allowed_parties'][] = $party->id;
            }
        } elseif (Fixometer::hasRole($user, 'Host')) {
            $data['groups'] = $Groups->ofThisUser($user->id);
            foreach ($data['groups'] as $i => $group) {
                $data['groups'][$i]->id = $group->idgroups;
                $data['group_ids'][] = $group->idgroups;
            }
            $data['parties'] = $Parties->ofTheseGroups($data['group_ids'], true);
            foreach ($data['parties'] as $i => $party) {
                $data['parties'][$i]->id = $party->idevents;
                $data['parties'][$i]->venue = is_null($data['parties'][$i]->venue) ? $data['parties'][$i]->location : $data['parties'][$i]->venue;
                $data['allowed_parties'][] = $party->idevents;
            }
        }
        foreach ($data['parties'] as $party) {
            $data['sorted_parties'][$party->group_name][] = $party;
        }
        return $data;
    }

    /**
     * Validate and process input filters.
     *
     * @return array
     */
    public static function getSearchFilters(Request $request)
    {
        $filters = [
            'searched_groups' => null,
            'searched_parties' => null,
            'from_date' => null,
            'to_date' => null,
            'group_tags' => null,
            'response' => null,
        ];

        if ($request->has('groups') && is_array($request->input('groups'))) {
            $filters['searched_groups'] = filter_var_array($request->input('groups'), FILTER_SANITIZE_NUMBER_INT);
        }

        if ($request->has('parties') && is_array($request->input('parties'))) {
            $filters['searched_parties'] = filter_var_array($request->input('parties'), FILTER_SANITIZE_NUMBER_INT);
        }

        if ($request->has('from-date') && !empty($request->input('from-date'))) {
            if (!DateTime::createFromFormat('Y-m-d', $request->input('from-date'))) {
                $filters['response'] = ['danger' => 'Invalid "From" date"'];
            } else {
                $fromDate = DateTime::createFromFormat('Y-m-d', $request->input('from-date'));
                $filters['from_date'] = strtotime($fromDate->format('Y-m-d'));
            }
        }

        if ($request->has('to-date') && !empty($request->input('to-date'))) {
            if (!DateTime::createFromFormat('Y-m-d', $request->input('to-date'))) {
                $filters['response'] = ['danger' => 'Invalid "To" date"'];
            } else {
                $toDate = DateTime::createFromFormat('Y-m-d', $request->input('to-date'));
                $filters['to_date'] = strtotime($toDate->format('Y-m-d'));
            }
        }

        if ($request->has('group_tags') && !empty($request->input('group_tags'))) {
            $filters['group_tags'] = $request->input('group_tags');
        }
        return $filters;
    }

    public static function debugParams($params)
    {
        foreach ($params as $k => $v) {
            $type = gettype($v);
            if (is_array($v)) {
                $type .= ' (' . count($v) . ')';
            }
            logger($k . ' => ' . $type);
        }
    }

}
