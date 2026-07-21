<?php

namespace App\Http\Controllers\API;

use App\Device;
use App\EventsUsers;
use App\Events\UserFollowedGroup;
use App\Group;
use App\Helpers\Fixometer;
use App\Helpers\Tus;
use App\Http\Controllers\Controller;
use App\Invite;
use App\Notifications\JoinGroup;
use App\Notifications\NewGroupMember;
use App\Party;
use App\Role;
use App\User;
use App\UserGroups;
use App\Xref;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Group membership and lifecycle: self-join/leave, nearby groups, invites,
 * archive, stats and image upload/delete. Replaces the equivalent Blade
 * GET-mutation routes on App\Http\Controllers\GroupController.
 */
class GroupMembershipController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/v2/groups/{id}/members/me",
     *      operationId="joinGroupv2",
     *      tags={"Groups"},
     *      summary="Join a group as the current user",
     *      description="403 is also returned when the authenticated user has not yet given the required data consents (see GET /api/v2/session).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Joined (or already a member)",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="joined", type="boolean"),
     *              @OA\Property(property="already_member", type="boolean")
     *          ))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function joinv2(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $alreadyMember = UserGroups::where('group', $group->idgroups)
            ->where('user', $user->id)
            ->where('status', 1)
            ->exists();

        if (! $alreadyMember) {
            UserGroups::updateOrCreate([
                'user' => $user->id,
                'group' => $group->idgroups,
            ], [
                'status' => 1,
                'role' => Role::RESTARTER,
            ]);

            event(new UserFollowedGroup($user, $group));

            $groupHosts = UserGroups::where('group', $group->idgroups)->where('role', Role::HOST)->get();

            foreach ($groupHosts as $groupHostLink) {
                $host = User::find($groupHostLink->user);

                if ($host) {
                    Notification::send($host, new NewGroupMember([
                        'user_name' => $user->name,
                        'group_name' => $group->name,
                        'group_url' => url('/group/view/'.$group->idgroups),
                    ], $host));
                }
            }
        }

        return response()->json([
            'data' => [
                'joined' => true,
                'already_member' => $alreadyMember,
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/groups/{id}/members/me",
     *      operationId="leaveGroupv2",
     *      tags={"Groups"},
     *      summary="Leave a group as the current user",
     *      description="Idempotent: leaving a group you are not a member of still returns success. 403 is also returned when the authenticated user has not yet given the required data consents (see GET /api/v2/session).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Left",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="left", type="boolean")
     *          ))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function leavev2(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $member = UserGroups::where('group', $id)
            ->where('user', $user->id)
            ->where('status', 1)
            ->first();

        // Mirrors UserGroupsController::leave: a missing membership could just mean the user
        // already left (double-click, etc) - that's still a successful outcome.
        if ($member) {
            $member->delete();
        }

        return response()->json(['data' => ['left' => true]]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/groups/nearby",
     *      operationId="getNearbyGroupsv2",
     *      tags={"Groups"},
     *      summary="Groups near the current user's location",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Nearby groups (empty if the user has no location set)",
     *          @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(
     *              @OA\Property(property="id", type="integer"),
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="distance", type="number"),
     *              @OA\Property(property="location", type="string", nullable=true),
     *              @OA\Property(property="image_url", type="string", nullable=true)
     *          )))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function nearbyv2(Request $request): JsonResponse
    {
        $user = $request->user();

        // User::groupsNearby() already returns [] when the user has no lat/lng - no need to
        // special-case it here.
        $groups = $user->groupsNearby();

        return response()->json([
            'data' => array_map(fn (Group $group) => $group->toNearbySummary(), $groups),
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/groups/{id}/invites",
     *      operationId="inviteToGroupv2",
     *      tags={"Groups"},
     *      summary="Invite people to a group by email",
     *      description="Requires administrator, network-coordinator-for-group, or host-of-group permission. Mirrors GroupController@postSendInvite. 403 is also returned when the authenticated user has not yet given the required data consents (see GET /api/v2/session).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"emails"},
     *              @OA\Property(property="emails", type="array", @OA\Items(type="string")),
     *              @OA\Property(property="message", type="string", nullable=true)
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Invites processed",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="invites_sent", type="integer"),
     *              @OA\Property(property="invalid", type="array", @OA\Items(type="string"))
     *          ))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function invitesv2(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $this->authorizeGroupEdit($user, $group);

        $request->validate([
            'emails' => 'required|array|min:1',
            'emails.*' => 'string',
            'message' => 'nullable|string',
        ]);

        $message = $request->input('message');

        $valid = [];
        $invalid = [];

        foreach ($request->input('emails') as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $valid[] = $email;
            } else {
                $invalid[] = $email;
            }
        }

        $existingUsers = User::whereIn('email', $valid)->get();
        $nonUserEmails = array_values(array_diff($valid, $existingUsers->pluck('email')->all()));

        foreach ($existingUsers as $existingUser) {
            $userGroup = UserGroups::where('user', $existingUser->id)->where('group', $group->idgroups)->first();

            // Already a confirmed member, or already has an outstanding invite - nothing to do.
            if ($userGroup && $userGroup->status == '1') {
                continue;
            }

            $hash = Fixometer::generateHash();
            $url = url('/group/accept-invite/'.$group->idgroups.'/'.$hash);

            if ($userGroup) {
                $userGroup->update(['status' => $hash]);
            } else {
                UserGroups::create([
                    'user' => $existingUser->id,
                    'group' => $group->idgroups,
                    'status' => $hash,
                    'role' => Role::RESTARTER,
                ]);
            }

            if ($existingUser->invites == 1) {
                Notification::send($existingUser, new JoinGroup([
                    'name' => $user->name,
                    'group' => $group->name,
                    'url' => $url,
                    'message' => $message,
                ], $existingUser));
            }
        }

        foreach ($nonUserEmails as $nonUserEmail) {
            $hash = Fixometer::generateHash();

            $invite = Invite::create([
                'record_id' => $group->idgroups,
                'email' => $nonUserEmail,
                'hash' => $hash,
                'type' => 'group',
            ]);

            Notification::send($invite, new JoinGroup([
                'name' => $user->name,
                'group' => $group->name,
                'url' => url('/user/register/'.$hash),
                'message' => $message,
            ]));
        }

        return response()->json([
            'data' => [
                'invites_sent' => count($valid),
                'invalid' => $invalid,
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/groups/{id}",
     *      operationId="archiveGroupv2",
     *      tags={"Groups"},
     *      summary="Archive a group",
     *      description="Sets archived_at; does not delete the group or its history. Requires administrator or network-coordinator-for-group permission (mirrors the Group resource's can_perform_archive flag). Also requires the group to have no event with a device (Group::canDelete()), and returns 403 when the authenticated user has not yet given the required data consents (see GET /api/v2/session).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Archived",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="archived", type="boolean")
     *          ))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function archivev2(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $isAdministrator = Fixometer::hasRole($user, 'Administrator');
        $isCoordinatorForGroup = $user->isCoordinatorForGroup($group);

        if (! $isAdministrator && ! $isCoordinatorForGroup) {
            abort(403);
        }

        // Preserve the pre-cutover protection: a group with an event that has a
        // device cannot be removed (Group::canDelete()). This mirrors the old
        // GET /group/delete rule (which redirected to /user/forbidden) and the
        // can_perform_delete flag the SPA gates its delete button on
        // (GroupController::groupPermissionsFor).
        if (! $group->canDelete()) {
            abort(403);
        }

        $group->update(['archived_at' => now()]);

        return response()->json(['data' => ['archived' => true]]);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/groups/{id}/permanent",
     *      operationId="deleteGroupPermanentlyv2",
     *      tags={"Groups"},
     *      summary="Permanently delete a group and its events",
     *      description="Administrator only, and only for a group with no event that has a device (Group::canDelete). This is the hard delete - DELETE /api/v2/groups/{id} archives instead, which is reversible and available to coordinators too.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", description="Group id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Deleted",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="deleted", type="boolean")
     *          ))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function deletePermanentlyv2(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        // Administrator only - stricter than archive, which coordinators may
        // also do. Matches can_see_delete in GroupController::groupPermissionsFor
        // and the Auth::user()->hasRole('Administrator') gate on the web
        // GroupController::delete this replaces.
        if (! Fixometer::hasRole($user, 'Administrator')) {
            abort(403);
        }

        if (! $group->canDelete()) {
            abort(403);
        }

        // The group's events go too, including soft-deleted and future ones.
        // canDelete() has already established none of them has a device, so
        // nothing with recorded repair data is destroyed here. events_users
        // rows are not cascaded in the DB, so they need removing first or the
        // event delete hits a constraint violation - force, not soft, for the
        // same reason.
        $events = Party::withTrashed()->where('events.group', $group->idgroups)->get();

        foreach ($events as $event) {
            EventsUsers::where('event', $event->idevents)->get()
                ->each(fn ($eventUser) => $eventUser->forceDelete());

            $event->forceDelete();
        }

        $group->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/groups/{id}/stats",
     *      operationId="getGroupStatsv2",
     *      tags={"Groups"},
     *      summary="Statistics block for the group view page",
     *      description="Mirrors the group_stats/device_stats/cluster_stats/top_devices props GroupController@view (Blade) passes to group/view.blade.php.",
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Group statistics",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="group_stats", type="object"),
     *              @OA\Property(property="device_stats", type="object",
     *                  @OA\Property(property="fixed", type="integer"),
     *                  @OA\Property(property="repairable", type="integer"),
     *                  @OA\Property(property="dead", type="integer")
     *              ),
     *              @OA\Property(property="cluster_stats", type="object", description="Keyed by cluster id (1-4)"),
     *              @OA\Property(property="top_devices", type="array", @OA\Items(
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="counter", type="integer")
     *              ))
     *          ))
     *      ),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function statsv2(Request $request, $id): JsonResponse
    {
        $group = Group::findOrFail($id);
        $device = new Device();

        return response()->json([
            'data' => [
                'group_stats' => $group->getGroupStats(),
                'device_stats' => self::deviceStats($device, $group->idgroups),
                'cluster_stats' => self::clusterStats($device, $group->idgroups),
                'top_devices' => self::topDevices($device, $group->idgroups),
            ],
        ]);
    }

    private static function deviceStats(Device $device, int $idgroups): array
    {
        $stats = ['fixed' => 0, 'repairable' => 0, 'dead' => 0];

        foreach ($device->statusCount($idgroups) as $count) {
            if ($count->status == Device::REPAIR_STATUS_FIXED) {
                $stats['fixed'] = (int) $count->counter;
            } elseif ($count->status == Device::REPAIR_STATUS_REPAIRABLE) {
                $stats['repairable'] = (int) $count->counter;
            } elseif ($count->status == Device::REPAIR_STATUS_ENDOFLIFE) {
                $stats['dead'] = (int) $count->counter;
            }
        }

        return $stats;
    }

    private static function clusterStats(Device $device, int $idgroups): array
    {
        // Same template/accumulation as GroupController@view (Blade): repair_status 1/2/3 land
        // in array positions 0/1/2 respectively.
        $template = [
            0 => ['counter' => 0],
            1 => ['counter' => 0],
            2 => ['counter' => 0],
            'total' => 0,
        ];
        $clusters = [1 => $template, 2 => $template, 3 => $template, 4 => $template];

        foreach ($device->countByClustersYearStatus($idgroups) as $count) {
            $cluster = $count->cluster;
            $repair_status = $count->repair_status;

            if ($repair_status && $cluster && array_key_exists($cluster, $clusters)) {
                $clusters[$cluster][$repair_status - 1]['counter'] += $count->counter;
                $clusters[$cluster]['total'] += $count->counter;
            }
        }

        $ret = [];

        foreach ([1, 2, 3, 4] as $cluster) {
            $mostSeen = $device->findMostSeen(null, $cluster, $idgroups);
            $mostRepaired = $device->findMostSeen(Device::REPAIR_STATUS_FIXED, $cluster, $idgroups);
            $leastRepaired = $device->findMostSeen(Device::REPAIR_STATUS_ENDOFLIFE, $cluster, $idgroups);

            $ret[$cluster] = [
                'fixed' => (int) $clusters[$cluster][0]['counter'],
                'repairable' => (int) $clusters[$cluster][1]['counter'],
                'dead' => (int) $clusters[$cluster][2]['counter'],
                'total' => (int) $clusters[$cluster]['total'],
                'most_seen' => self::namedCount($mostSeen),
                'most_repaired' => self::namedCount($mostRepaired),
                'least_repaired' => self::namedCount($leastRepaired),
            ];
        }

        return $ret;
    }

    private static function namedCount(array $rows): array
    {
        return [
            'name' => $rows[0]->name ?? null,
            'count' => isset($rows[0]->counter) ? (int) $rows[0]->counter : 0,
        ];
    }

    private static function topDevices(Device $device, int $idgroups): array
    {
        $rows = $device->findMostSeen(Device::REPAIR_STATUS_FIXED, null, $idgroups);

        return array_map(fn ($row) => ['name' => $row->name, 'counter' => (int) $row->counter], $rows);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/groups/{id}/images",
     *      operationId="uploadGroupImagev2",
     *      tags={"Groups"},
     *      summary="Attach a completed tus upload as the group's image",
     *      description="Mirrors UserController::updateMyPhotov2 - upload the file to /api/tus first, then attach it here by upload_key. Requires administrator, network-coordinator-for-group, or host-of-group permission; 403 is also returned when the authenticated user has not yet given the required data consents (see GET /api/v2/session). 422 covers a missing/expired/oversized (>2MB) upload_key or a non jpeg/png/gif upload.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(required={"upload_key"}, @OA\Property(property="upload_key", type="string"))
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Image attached",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="image_url", type="string")
     *          ))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function uploadImagev2(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $this->authorizeGroupEdit($user, $group);

        $validated = $request->validate([
            'upload_key' => 'required|string',
        ]);

        $cache = Tus::buildCache();
        $meta = $cache->get($validated['upload_key']);
        $filePath = $meta['file_path'] ?? null;

        if (! $meta || ! $filePath || ! is_file($filePath)) {
            throw ValidationException::withMessages([
                'upload_key' => [__('groups.image_upload_error')],
            ]);
        }

        // Confirm the tus upload actually finished (offset === size), not just started.
        if (($meta['offset'] ?? null) !== ($meta['size'] ?? null)) {
            throw ValidationException::withMessages([
                'upload_key' => [__('groups.image_upload_error')],
            ]);
        }

        // Max 2MB, matching the profile-photo contract (updateMyPhotov2).
        if (filesize($filePath) > 2 * 1024 * 1024) {
            $cache->delete($validated['upload_key']);
            @unlink($filePath);

            throw ValidationException::withMessages([
                'upload_key' => [__('groups.image_upload_error')],
            ]);
        }

        $mime = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
            $cache->delete($validated['upload_key']);
            @unlink($filePath);

            throw ValidationException::withMessages([
                'upload_key' => [__('groups.image_upload_error')],
            ]);
        }

        $file = new \FixometerFile();
        // $clear is hardcoded true inside uploadLocalFile(), so any existing group image xref
        // is removed automatically before this one is attached.
        $filename = $file->uploadLocalFile($filePath, 'image', $group->idgroups, env('TBL_GROUPS'), false, true);

        $cache->delete($validated['upload_key']);
        @unlink($filePath);

        if (! $filename) {
            throw ValidationException::withMessages([
                'upload_key' => [__('groups.image_upload_error')],
            ]);
        }

        return response()->json([
            'data' => [
                'image_url' => url('/uploads/mid_'.$filename),
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/groups/{id}/images/{idimages}",
     *      operationId="deleteGroupImagev2",
     *      tags={"Groups"},
     *      summary="Detach an image from a group",
     *      description="Requires administrator, network-coordinator-for-group, or host-of-group permission; 403 is also returned when the authenticated user has not yet given the required data consents (see GET /api/v2/session). 404 covers both an unknown group id and an idimages that doesn't reference an image on this group.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="idimages", description="The xref id linking the image to the group", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Deleted",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="deleted", type="boolean")
     *          ))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function deleteImagev2(Request $request, $id, $idimages): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $this->authorizeGroupEdit($user, $group);

        // Scope the xref lookup to this group - unlike the legacy Blade ajaxDeleteImage(), which
        // deletes by xref id alone with no ownership check.
        $xref = Xref::where('idxref', $idimages)
            ->where('reference', $group->idgroups)
            ->where('reference_type', env('TBL_GROUPS'))
            ->first();

        if (! $xref) {
            abort(404, 'Image not found for this group.');
        }

        // Matches FixometerFile::deleteImage(): remove the xref only, leaving the underlying
        // file/images row in place.
        $xref->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * The "can edit this group" permission check shared by invites/image endpoints: mirrors
     * updateGroupv2's gate (administrator, network coordinator for the group, or host of the
     * group).
     */
    private function authorizeGroupEdit(User $user, Group $group): void
    {
        $isAdministrator = Fixometer::hasRole($user, 'Administrator');
        $isHostOfGroup = Fixometer::userHasEditGroupPermission($group->idgroups, $user->id);
        $isCoordinatorForGroup = $user->isCoordinatorForGroup($group);

        if (! $isAdministrator && ! $isHostOfGroup && ! $isCoordinatorForGroup) {
            abort(403);
        }
    }
}
