<?php

namespace App\Http\Controllers;

use App\Helpers\SearchHelper;
use App\Party;
use App\Search;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $dropdowns = SearchHelper::getUserGroupsAndParties();

        // always need to send these, whatever the outcome
        $params = SearchHelper::getDefaultViewParams();
        $params['groups'] = $dropdowns['groups'];
        $params['sorted_parties'] = $dropdowns['sorted_parties'];

        if ($request->has('fltr') && ! empty($request->input('fltr'))) {
            // validate filters

            $filters = SearchHelper::getSearchFilters($request);
            if (! empty($filters['response'])) {
                // validation error - return error message

                $params['response'] = $filters['response'];
            } else {
                // execute the search

                $Search = new Search;
                $PartyList = $Search->parties(
                    $filters['searched_parties'],
                    $filters['searched_groups'],
                    $filters['from_date'],
                    $filters['to_date'],
                    $filters['group_tags'],
                    $dropdowns['allowed_parties']
                );

                // Send these to getEventStats() to speed things up a bit.
                $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
                $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

                if (count($PartyList) > 0) {
                    $partyIds = [];

                    /// waste stats
                    $stats = Party::getEventStatsArrayKeys();
                    foreach ($PartyList as $party) {
                        $partyIds[] = $party->idevents;

                        $eventStats = $party->getEventStats($eEmissionRatio, $uEmissionratio);
                        foreach (array_keys($stats) as $v) {
                            $party->{$v} = $eventStats[$v];
                            $stats[$v] += $eventStats[$v];
                        }
                    }

                    // cluster dataviz
                    $clusters = [];
                    for ($i = 1; $i <= 4; $i++) {
                        $cluster = $Search->countByCluster($partyIds, $i);
                        $total = 0;
                        foreach ($cluster as $state) {
                            $total += $state->counter;
                        }
                        $cluster['total'] = $total;
                        $clusters['all'][$i] = $cluster;
                    }

                    // most/least stats for clusters
                    $mostleast = [];
                    for ($i = 1; $i <= 4; $i++) {
                        $mostleast[$i]['most_seen'] = $Search->findMostSeen($partyIds, null, $i);
                        $mostleast[$i]['most_repaired'] = $Search->findMostSeen($partyIds, 1, $i);
                        $mostleast[$i]['least_repaired'] = $Search->findMostSeen($partyIds, 3, $i);
                    }

                    $params['PartyList'] = $PartyList;
                    $params['mostleast'] = $mostleast;
                    $params['clusters'] = $clusters;
                    $params['category_clusters'] = \App\Helpers\Fixometer::categoryCluster();
                    $params['stats'] = $stats;
                    $params['device_count_status'] = $Search->deviceStatusCount($partyIds);
                    $params['top'] = $Search->findMostSeen($partyIds, 1, null);
                } else {
                    // return no-results message

                    // Don't log to Sentry - legitimate user error.
                    $params['response'] = ['warning' => 'No results for these filters'];
                }
            }
        }
        // finally, return the view
        return view('search.index', $params);
    }
}
