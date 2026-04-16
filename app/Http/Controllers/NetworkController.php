<?php

namespace App\Http\Controllers;

use App\Attributes\Feature;
use App\Attributes\UserStory;
use App\Group;
use App\Network;
use Auth;
use FixometerFile;
use Illuminate\Http\Request;
use Lang;

#[Feature('Networks', description: 'Regional network management and coordination')]
class NetworkController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    #[UserStory('As a NetworkCoordinator, I can view the networks I coordinate', persona: 'NetworkCoordinator', theme: 'Browse networks')]
    #[UserStory('As an Admin, I can view all networks on the platform', persona: 'Admin', theme: 'Browse networks')]
    public function index()
    {
        $user = Auth::user();

        if (! $user->hasRole('NetworkCoordinator') && ! $user->hasRole('Administrator')) {
            abort(403);
        }

        $yourNetworks = $user->networks->sortBy('name');
        $allNetworks = [];
        $showAllNetworks = false;

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
     * Display the specified network.
     *
     * @param  \App\Network  $network
     * @return \Illuminate\Http\Response
     */
    #[UserStory('As a NetworkCoordinator, I can view my network\'s details and statistics', persona: 'NetworkCoordinator', theme: 'Browse networks')]
    public function show(Network $network)
    {
        $user = Auth::user();

        $this->authorize('view', $network);

        $groupsForAssociating = [];

        if ($user->can('associateGroups', $network)) {
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
    #[UserStory('As a NetworkCoordinator, I can access the form to edit my network', persona: 'NetworkCoordinator', theme: 'Manage network details')]
    public function edit(Network $network)
    {
        $this->authorize('update', $network);

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
    #[UserStory('As a NetworkCoordinator, I can update my network\'s details and logo', persona: 'NetworkCoordinator', theme: 'Manage network details')]
    public function update(Request $request, Network $network)
    {
        $this->authorize('update', $network);

        if ($request->hasFile('network_logo')) {
            // Save the file.
            $path = $request->file('network_logo')->store('network_logos', [
                'disk' => 'public_uploads',
            ]);

            // Store it in the network object.
            if ($path) {
                $network->logo = $path;
                $network->save();
            } else {
                abort(500, 'Failed to save logo');
            }
        }

        return redirect()->route('networks.edit', [$network]);
    }

    /**
     * Associate groups to the specified network.
     *
     * @param  \App\Network  $network
     * @return \Illuminate\Http\Response
     */
    #[UserStory('As a NetworkCoordinator, I can add groups to my network', persona: 'NetworkCoordinator', theme: 'Network groups & events')]
    public function associateGroup(Request $request, Network $network)
    {
        $this->authorize('associateGroups', $network);

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
}
