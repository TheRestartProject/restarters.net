<?php

namespace App\Http\Controllers;

use App\Attributes\Feature;
use App\Attributes\UserStory;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Group;
use App\GroupTags;
use App\Helpers\Fixometer;
use App\Network;
use App\Role;
use Auth;
use FixometerFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Lang;

#[Feature('Networks', description: 'Regional network management and coordination')]
class NetworkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[UserStory('As a NetworkCoordinator, I can view the networks I coordinate', persona: 'NetworkCoordinator', theme: 'Browse networks')]
    #[UserStory('As an Admin, I can view all networks on the platform', persona: 'Admin', theme: 'Browse networks')]
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
    /**
     * The groups in the network nearest to one of the user's own groups in it, preferring one they host.  Null if
     * they have no located group in the network.
     */
    private function nearbyGroups(Network $network, $user): ?array
    {
        $mine = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->join('group_network', 'group_network.group_id', '=', 'groups.idgroups')
            ->where('group_network.network_id', $network->id)
            ->where('users_groups.user', $user->id)
            ->whereNull('users_groups.deleted_at')
            ->whereNotNull('groups.latitude')
            ->whereNotNull('groups.longitude')
            ->orderByRaw('users_groups.role = ? DESC', [Role::HOST])
            ->orderBy('groups.name')
            ->select('groups.*')
            ->first();

        if (! $mine) {
            return null;
        }

        return [
            'from' => $mine->name,
            'groups' => $network->nearestGroups($mine)->map(fn ($g) => [
                'id' => $g->idgroups,
                'name' => $g->name,
                'location' => $g->location,
                'distance' => round($g->dist, 1),
            ])->values()->all(),
        ];
    }

    #[UserStory('As a NetworkCoordinator, I can view my network\'s details and statistics', persona: 'NetworkCoordinator', theme: 'Browse networks')]
    public function show(Network $network): View
    {
        $user = Auth::user();

        // Coordinators and admins get the full page; hosts of groups in the network get it without the
        // coordinator-only parts.
        $this->authorize('viewPublic', $network);

        $canModerate = $user->can('view', $network);

        $groupsForAssociating = [];

        if ($user->can('associateGroups', $network)) {
            $groupsForAssociating = $network->groupsNotIn();
        }

        // Stats are fetched by the page from the API after it renders.  Calculating them walks every device
        // of every past event in the network, which is too slow to block the page load on for large networks.
        $stats = [];

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
            'reporting_url' => $canModerate ? $network->reporting_url : null,
            'logo' => $network->sizedLogo('_x100'),
            // Organisers can see who their coordinators are, so they know who to contact.
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
            'canModerate' => $canModerate,
            'nearbyGroups' => $this->nearbyGroups($network, $user),
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
    #[UserStory('As a NetworkCoordinator, I can access the form to edit my network', persona: 'NetworkCoordinator', theme: 'Manage network details')]
    public function edit(Network $network): View
    {
        $this->authorize('update', $network);

        return view('networks.edit', [
            'network' => $network,
            'canEditReportingUrl' => Fixometer::hasRole(Auth::user(), 'Administrator'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    #[UserStory('As a NetworkCoordinator, I can update my network\'s details and logo', persona: 'NetworkCoordinator', theme: 'Manage network details')]
    public function update(Request $request, Network $network): RedirectResponse
    {
        $this->authorize('update', $network);

        // The reporting dashboard address is set up by administrators alongside the dashboard itself.
        if (Fixometer::hasRole(Auth::user(), 'Administrator') && $request->has('reporting_url')) {
            $request->validate([
                'reporting_url' => 'nullable|url:https,http|max:2048',
            ]);

            $network->reporting_url = $request->input('reporting_url') ?: null;
            $network->save();
        }

        if ($request->hasFile('network_logo')) {
            if (! config('restarters.features.image_upload')) {
                return redirect()->route('networks.edit', [$network])
                    ->withWarning('Image uploads are disabled on this site.');
            }

            // This is the one upload path that doesn't go through FixometerFile, which
            // restricts uploads to jpg/png/gif by sniffing the content.  Without a rule
            // here the extension comes from whatever mime type is detected, so an SVG -
            // which can carry script and is served from our own origin - would be stored.
            $request->validate([
                'network_logo' => 'image|mimes:jpeg,jpg,png,gif|max:5120',
            ]);

            // Determine the correct disk to use (s3 on Fly, public_uploads in dev)
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public_uploads';

            // Save the file.
            $path = $request->file('network_logo')->store('network_logos', [
                'disk' => $disk,
            ]);

            // Store it in the network object.
            if ($path) {
                // Generate the _x100 sized version by copying the file
                $sizedPath = preg_replace('/\.([^.\s]{3,4})$/', '-_x100.$1', $path);
                $storage = Storage::disk($disk);

                // Copy the uploaded file to the _x100 filename
                $storage->copy($path, $sizedPath);

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
    #[UserStory('As a NetworkCoordinator, I can add groups to my network', persona: 'NetworkCoordinator', theme: 'Network groups & events')]
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

        return redirect()->route('networks.show', [$network])->withSuccess(Lang::choice('networks.show.add_groups_success', $numberOfGroups, ['number' => $numberOfGroups]));
    }
}
