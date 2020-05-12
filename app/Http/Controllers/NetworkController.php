<?php

namespace App\Http\Controllers;

use App\Group;
use App\Network;
use FixometerFile;

use Illuminate\Http\Request;

use Auth;

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
            'network' => $network
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
            $networkLogoPath = env('UPLOADS_URL').'mid_'.$networkLogoFilename;
        }

        return redirect()->route('networks.edit', [$network]);
    }

    /**
     * Associate a group to the specified network.
     *
     * @param  \App\Network  $network
     * @return \Illuminate\Http\Response
     */
    public function associateGroup(Request $request, Network $network)
    {
        // TODO: authorisation?

        $groupId = $request->input('group');

        $group = Group::find($groupId);

        // TODO: validation of group
        if ( ! is_null($group)) {
            $network->addGroup($group);
        }

        return redirect()->route('networks.show', [$network])->withSuccess($group->name.' added');
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
