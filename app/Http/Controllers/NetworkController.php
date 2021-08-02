<?php

namespace App\Http\Controllers;

use App\Group;
use App\Network;
use Auth;
use FixometerFile;
use Illuminate\Http\Request;
use Lang;

class NetworkController extends Controller
{
    protected $crossReferenceTableId;

    public function __construct()
    {
        $this->crossReferenceTableId = config('restarters.xref_types.networks');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();

        if (! $user->hasRole('NetworkCoordinator') && ! $user->hasRole('Administrator')) {
            abort(403);
        }

        $yourNetworks = $user->networks->sortBy('name');

        if ($user->hasRole('Administrator')) {
            $showAllNetworks = true;
            $allNetworks = Network::orderBy('name')->get();
        }

        return view('networks.index', [
            'yourNetworks' => $yourNetworks,
            'allNetworks' => $allNetworks,
            'showAllNetworks' => $showAllNetworks,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified network.
     *
     * @param  \App\Network  $network
     * @return \Illuminate\Http\Response
     */
    public function show(Network $network)
    {
        $this->authorize('view', $network);

        $groupsForAssociating = [];
        if (Auth::user()->can('associateGroups', $network)) {
            $groupsForAssociating = $network->groupsNotIn()->sortBy('name');
        }

        return view('networks.show', [
            'network' => $network,
            'groupsForAssociating' => $groupsForAssociating,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Network  $network
     * @return \Illuminate\Http\Response
     */
    public function edit(Network $network)
    {
        // TODO: authorisation?

        return view('networks.edit', [
            'network' => $network,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Network  $network
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Network $network)
    {
        // TODO: authorisation?

        if ($request->hasFile('network_logo')) {
            $fileHelper = new FixometerFile;
            $networkLogoFilename = $fileHelper->upload('network_logo', 'image', $network->id, $this->crossReferenceTableId, false, false, false, false);
        }

        return redirect()->route('networks.edit', [$network]);
    }

    /**
     * Associate groups to the specified network.
     *
     * @param  \App\Network  $network
     * @return \Illuminate\Http\Response
     */
    public function associateGroup(Request $request, Network $network)
    {
        // TODO: authorisation?

        $groupIds = $request->input('groups');

        if (is_null($groupIds)) {
            return redirect()->route('networks.show', [$network])->withWarning(Lang::get('networks.show.add_groups_warning_none_selected'));
        }

        foreach ($groupIds as $groupId) {
            $group = Group::find($groupId);
            $network->addGroup($group);
        }

        $numberOfGroups = count($groupIds);

        return redirect()->route('networks.show', [$network])->withSuccess(Lang::get('networks.show.add_groups_success', ['number' => $numberOfGroups]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Network  $network
     * @return \Illuminate\Http\Response
     */
    public function destroy(Network $network)
    {
        //
    }
}
