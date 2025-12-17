<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Group;
use App\GroupTags;
use App\Helpers\Fixometer;
use App\Network;
use Auth;
use FixometerFile;
use Illuminate\Http\Request;
use Lang;

class NetworkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
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
     */
    public function show(Network $network): View
    {
        $user = Auth::user();

        $this->authorize('view', $network);

        $groupsForAssociating = [];

        if ($user->can('associateGroups', $network)) {
            $groupsForAssociating = $network->groupsNotIn()->sortBy('name');
        }

        // Get network stats
        $stats = $network->stats();
        $stats['groups'] = $network->groups->count();

        // Determine if user can manage tags (NC for this network or Admin)
        $canManageTags = Fixometer::hasRole($user, 'Administrator') ||
            ($user->isCoordinatorOf($network));

        // Get tags for this network
        $tags = [];
        if ($canManageTags) {
            $tags = GroupTags::forNetwork($network->id)
                ->get()
                ->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->tag_name,
                        'description' => $tag->description,
                        'groups_count' => $tag->groupTagGroups()->count(),
                    ];
                });
        }

        // Prepare network data for Vue component
        $networkData = [
            'id' => $network->id,
            'name' => $network->name,
            'description' => $network->description,
            'website' => $network->website,
            'logo' => $network->sizedLogo('_x100'),
            'coordinators' => $network->coordinators->map(function ($c) {
                $profile = $c->getProfile($c->id);
                $path = $profile ? $profile->path : null;
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'picture' => $path ? '/uploads/thumbnail_' . $path : '/images/placeholder-avatar.png',
                ];
            }),
        ];

        return view('networks.show', [
            'network' => $network,
            'networkData' => $networkData,
            'groupsForAssociating' => $groupsForAssociating,
            'stats' => $stats,
            'tags' => $tags,
            'canManageTags' => $canManageTags,
            'canAssociateGroups' => $user->can('associateGroups', $network),
            'apiToken' => $user->api_token,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Network $network): View
    {
        $this->authorize('update', $network);

        return view('networks.edit', [
            'network' => $network,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Network $network): RedirectResponse
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
     */
    public function associateGroup(Request $request, Network $network): RedirectResponse
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
