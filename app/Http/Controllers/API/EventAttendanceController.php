<?php

namespace App\Http\Controllers\API;

use App\Audits;
use App\Device;
use App\Events\EventDeleted;
use App\Events\EventImagesUploaded;
use App\EventsUsers;
use App\Helpers\Fixometer;
use App\Helpers\Tus;
use App\Http\Controllers\Controller;
use App\Invite;
use App\Notifications\EventRepairs;
use App\Notifications\JoinEvent;
use App\Notifications\RSVPEvent;
use App\Party;
use App\Role;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Event attendance and lifecycle: RSVP self-join/leave, volunteer role/removal,
 * invites, image upload/delete, and delete. Replaces the equivalent Blade
 * GET-mutation routes on App\Http\Controllers\PartyController. Mirrors
 * App\Http\Controllers\API\GroupMembershipController's shape for the events side.
 */
class EventAttendanceController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/v2/events/{id}/attendees/me",
     *      operationId="rsvpEventv2",
     *      tags={"Events"},
     *      summary="RSVP to an event as the current user",
     *      description="Replaces GET /party/join/{id}. EventsUsers::updateOrCreate matches on user+event regardless of status, so this also resolves any pending hash-status invite row for this user into a confirmed RSVP - the in-app 'confirm RSVP' banner doesn't need to know the hash (judgment call #1 in api-contracts-phase-c.md). Emailed accept-invite links keep working via the frozen GET /party/accept-invite/{id}/{hash} redirect.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Attending (or already was)",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="attending", type="boolean"),
     *              @OA\Property(property="already_attending", type="boolean"),
     *              @OA\Property(property="prompt_follow_group", type="boolean", description="True when the user isn't a member of the hosting group - replaces the legacy prompt-follow-group flash")
     *          ))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function rsvpv2(Request $request, $idevents): JsonResponse
    {
        $user = $request->user();
        $event = Party::findOrFail($idevents);

        $alreadyAttending = EventsUsers::where('event', $idevents)
            ->where('user', $user->id)
            ->where('status', '1')
            ->exists();

        $userEvent = EventsUsers::updateOrCreate([
            'user' => $user->id,
            'event' => $idevents,
        ], [
            'status' => '1',
            'role' => Role::RESTARTER,
        ]);

        $promptFollowGroup = ! $user->isInGroup($event->theGroup->idgroups);

        if (! $alreadyAttending) {
            self::notifyHostsOfRsvp($userEvent, $idevents);
        }

        return response()->json([
            'data' => [
                'attending' => true,
                'already_attending' => $alreadyAttending,
                'prompt_follow_group' => $promptFollowGroup,
            ],
        ]);
    }

    /**
     * Mirrors PartyController::notifyHostsOfRsvp.
     */
    private static function notifyHostsOfRsvp(EventsUsers $userEvent, $eventId): void
    {
        $hosts = User::join('events_users', 'events_users.user', '=', 'users.id')
            ->where('events_users.event', $eventId)
            ->where('events_users.role', Role::HOST)
            ->select('users.*')
            ->get();

        if ($hosts->count()) {
            $rsvpUser = User::find($userEvent->user);
            $event = Party::find($eventId);

            Notification::send($hosts, new RSVPEvent([
                'user_name' => $rsvpUser->name,
                'event_venue' => $event->venue,
                'event_url' => url('/party/view/'.$eventId),
            ]));
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/events/{id}/attendees/me",
     *      operationId="cancelRsvpEventv2",
     *      tags={"Events"},
     *      summary="Cancel RSVP / decline an invite to an event as the current user",
     *      description="Replaces GET /party/cancel-invite/{id}. Idempotent: cancelling when not attending/invited still returns success. Loop-deletes rather than a bulk delete, to keep model observers firing (mirrors PartyController::cancelInvite).",
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
    public function cancelRsvpv2(Request $request, $idevents): JsonResponse
    {
        $user = $request->user();

        foreach (EventsUsers::where('user', $user->id)->where('event', $idevents)->get() as $row) {
            $row->delete();
        }

        return response()->json(['data' => ['left' => true]]);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/events/{id}/volunteers/{iduser}",
     *      operationId="patchEventVolunteerv2",
     *      tags={"Events","Volunteers"},
     *      summary="Set whether a volunteer is a host of the event",
     *      description="Sets EventsUsers.role to HOST/RESTARTER for that user's row(s) on this event. Requires host/network-coordinator/administrator permission (userHasEditPartyPermission, which already covers all three).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="iduser", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\RequestBody(required=true, @OA\JsonContent(required={"host"}, @OA\Property(property="host", type="boolean"))),
     *      @OA\Response(
     *          response=200,
     *          description="Updated",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="host", type="boolean")
     *          ))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function patchVolunteerv2(Request $request, $idevents, $iduser): JsonResponse
    {
        $user = $request->user();
        Party::findOrFail($idevents);
        User::findOrFail($iduser);

        if (! Fixometer::userHasEditPartyPermission($idevents, $user->id)) {
            abort(403);
        }

        $request->validate(['host' => 'required|boolean']);
        $host = $request->boolean('host');

        foreach (EventsUsers::where('event', $idevents)->where('user', $iduser)->get() as $row) {
            $row->role = $host ? Role::HOST : Role::RESTARTER;
            $row->save();
        }

        return response()->json(['data' => ['host' => $host]]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/events/{id}/request-review",
     *      operationId="requestEventReview",
     *      tags={"Events"},
     *      summary="Ask attendees to review the event's repairs",
     *      description="Sends the EventRepairs notification to every confirmed restarter who attended, asking them to review/contribute to the repair records. Requires host/coordinator/administrator permission (userHasEditPartyPermission).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", description="Event id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Requests sent",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="requested", type="integer")))),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     * )
     *
     * Port of PartyController::getContributions (the old "Request review" modal
     * on the event page linking to GET /party/contribution/{id}).
     */
    public function requestReviewv2(Request $request, $idevents): JsonResponse
    {
        $user = $request->user();
        $event = Party::findOrFail($idevents);

        if (! Fixometer::userHasEditPartyPermission($idevents, $user->id)) {
            abort(403);
        }

        // Confirmed restarters (status 1, role RESTARTER) who attended.
        $restarters = User::join('events_users', 'events_users.user', '=', 'users.id')
            ->where('events_users.status', 1)
            ->where('events_users.role', Role::RESTARTER)
            ->where('events_users.event', $idevents)
            ->select('users.*')
            ->get();

        Notification::send($restarters, new EventRepairs([
            'event_name' => $event->getEventName(),
            'event_url' => rtrim(config('restarters.frontend_url'), '/').'/party/view/'.intval($idevents).'#devices',
        ]));

        return response()->json(['data' => ['requested' => $restarters->count()]]);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/events/{id}/volunteers/{idevents_users}",
     *      operationId="deleteEventVolunteerv2",
     *      tags={"Events","Volunteers"},
     *      summary="Remove a volunteer from an event",
     *      description="Keyed by the events_users row id, NOT the user id (judgment call #2 in api-contracts-phase-c.md): a manually-added volunteer may have no associated user, so the row id is the only stable key. Mirrors POST /party/remove-volunteer. Idempotent: removing an already-removed/unknown row still returns success.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="idevents_users", description="The events_users row id (Attendee.id from GET .../attendees)", required=true, in="path", @OA\Schema(type="integer")),
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
    public function deleteVolunteerv2(Request $request, $idevents, $idevents_users): JsonResponse
    {
        $user = $request->user();
        Party::findOrFail($idevents);

        if (! Fixometer::userHasEditPartyPermission($idevents, $user->id)) {
            abort(403);
        }

        $volunteer = EventsUsers::where('idevents_users', $idevents_users)->where('event', $idevents)->first();
        $deleted = false;

        if ($volunteer) {
            $volunteer->delete();
            $deleted = true;
        }

        return response()->json(['data' => ['deleted' => $deleted]]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/events/{id}/invites",
     *      operationId="inviteToEventv2",
     *      tags={"Events"},
     *      summary="Invite people to an event by email",
     *      description="Requires administrator, network-coordinator-for-group, or host-of-group permission. Mirrors PartyController@postSendInvite and B2's POST /api/v2/groups/{id}/invites. Known emails get an in-app invite (hash-status row + JoinEvent notification); unknown emails get an Invite row (type 'event') + registration email; already-confirmed users are silently skipped; only malformed addresses are reported as invalid.",
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
    public function invitesv2(Request $request, $idevents): JsonResponse
    {
        $user = $request->user();
        $event = Party::findOrFail($idevents);

        if (! Fixometer::userHasEditPartyPermission($idevents, $user->id)) {
            abort(403);
        }

        $request->validate([
            'emails' => 'required|array|min:1',
            'emails.*' => 'string',
            'message' => 'nullable|string',
        ]);

        $message = $request->input('message');
        $groupName = $event->theGroup->name;

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
            $userEvent = EventsUsers::where('user', $existingUser->id)->where('event', $idevents)->first();

            // Already confirmed - nothing to do.
            if ($userEvent && (string) $userEvent->status === '1') {
                continue;
            }

            $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);
            $url = url('/party/accept-invite/'.$idevents.'/'.$hash);

            if ($userEvent) {
                $userEvent->update(['status' => $hash]);
            } else {
                EventsUsers::create([
                    'user' => $existingUser->id,
                    'event' => $idevents,
                    'status' => $hash,
                    'role' => Role::RESTARTER,
                ]);
            }

            Notification::send($existingUser, new JoinEvent([
                'name' => $user->name,
                'group' => $groupName,
                'url' => $url,
                'view_url' => url('/party/view/'.$idevents),
                'message' => $message,
                'event' => $event,
            ], $existingUser));
        }

        foreach ($nonUserEmails as $nonUserEmail) {
            $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);

            $invite = Invite::create([
                'record_id' => $idevents,
                'email' => $nonUserEmail,
                'hash' => $hash,
                'type' => 'event',
            ]);

            Notification::send($invite, new JoinEvent([
                'name' => $user->name,
                'group' => $groupName,
                'url' => url('/user/register/'.$hash),
                'view_url' => url('/party/view/'.$idevents),
                'message' => $message,
                'event' => $event,
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
     * @OA\Post(
     *      path="/api/v2/events/{id}/images",
     *      operationId="uploadEventImagev2",
     *      tags={"Events"},
     *      summary="Attach a completed tus upload as an event photo",
     *      description="Mirrors GroupMembershipController::uploadImagev2 - upload the file to /api/tus first, then attach it here by upload_key. Unlike group/profile images, event photos are a gallery (multiple per event, mirroring the legacy multi-file imageUpload()) so an upload never clears previous photos. Permission: any attendee (any EventsUsers row, any status) or Administrator - mirrors PartyController::deleteImage, looser than edit-party since any attendee can add repair photos.",
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
    public function uploadImagev2(Request $request, $idevents): JsonResponse
    {
        $user = $request->user();
        $event = Party::findOrFail($idevents);

        self::authorizeEventImageEdit($user, $idevents);

        $validated = $request->validate([
            'upload_key' => 'required|string',
        ]);

        $filePath = self::validatedTusFilePath($validated['upload_key'], 'events');

        $file = new \FixometerFile();
        // $clear=false: event photos are a gallery, not a single image like a group/profile photo.
        $filename = $file->uploadLocalFile($filePath, 'image', $event->idevents, env('TBL_EVENTS'), false, true, false);

        $cache = Tus::buildCache();
        $cache->delete($validated['upload_key']);
        @unlink($filePath);

        if (! $filename) {
            throw ValidationException::withMessages([
                'upload_key' => [__('events.image_upload_error')],
            ]);
        }

        // Restore the moderation-notification hook the Blade PartyController
        // fired (the SendAdminModerateEventPhotosNotification listener is still
        // registered, and throttles per-admin, so firing per upload is safe).
        event(new EventImagesUploaded($event, $user->id));

        return response()->json([
            'data' => [
                'image_url' => url('/uploads/mid_'.$filename),
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/events/{id}/images/{idimages}",
     *      operationId="deleteEventImagev2",
     *      tags={"Events"},
     *      summary="Detach a photo from an event",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="idimages", description="The xref id linking the image to the event", required=true, in="path", @OA\Schema(type="integer")),
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
    public function deleteImagev2(Request $request, $idevents, $idimages): JsonResponse
    {
        $user = $request->user();
        $event = Party::findOrFail($idevents);

        self::authorizeEventImageEdit($user, $idevents);

        $xref = \App\Xref::where('idxref', $idimages)
            ->where('reference', $event->idevents)
            ->where('reference_type', env('TBL_EVENTS'))
            ->first();

        if (! $xref) {
            abort(404, 'Image not found for this event.');
        }

        $xref->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * Shared "can edit this event's photos" gate for uploadImagev2/deleteImagev2: mirrors
     * PartyController::deleteImage - any attendee (any EventsUsers row, regardless of status,
     * so a pending invitee can still add photos) or an Administrator.
     */
    private static function authorizeEventImageEdit(User $user, $idevents): void
    {
        $isAdministrator = Fixometer::hasRole($user, 'Administrator');
        $isAttendee = EventsUsers::where('event', $idevents)->where('user', $user->id)->exists();

        if (! $isAdministrator && ! $isAttendee) {
            abort(403);
        }
    }

    /**
     * Shared tus-upload validation for event/device image endpoints: confirms the upload_key
     * resolves to a completed (offset===size), <=2MB, jpeg/png/gif upload. Returns the local
     * file path on success; throws a 422 ValidationException (and cleans up the cache/file)
     * otherwise. Mirrors GroupMembershipController::uploadImagev2's inline validation.
     */
    public static function validatedTusFilePath(string $uploadKey, string $translationFile): string
    {
        $cache = Tus::buildCache();
        $meta = $cache->get($uploadKey);
        $filePath = $meta['file_path'] ?? null;

        if (! $meta || ! $filePath || ! is_file($filePath)) {
            throw ValidationException::withMessages([
                'upload_key' => [__($translationFile.'.image_upload_error')],
            ]);
        }

        if (($meta['offset'] ?? null) !== ($meta['size'] ?? null)) {
            throw ValidationException::withMessages([
                'upload_key' => [__($translationFile.'.image_upload_error')],
            ]);
        }

        if (filesize($filePath) > 2 * 1024 * 1024) {
            $cache->delete($uploadKey);
            @unlink($filePath);

            throw ValidationException::withMessages([
                'upload_key' => [__($translationFile.'.image_upload_error')],
            ]);
        }

        $mime = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
            $cache->delete($uploadKey);
            @unlink($filePath);

            throw ValidationException::withMessages([
                'upload_key' => [__($translationFile.'.image_upload_error')],
            ]);
        }

        return $filePath;
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/events/{id}",
     *      operationId="deleteEventv2",
     *      tags={"Events"},
     *      summary="Delete an event",
     *      description="Permission = userHasEditPartyPermission || userIsHostOfGroup, matching PartyController::deleteEvent. Deletes audits, hard-deletes the event's devices, loop-deletes EventsUsers (for observers), soft-deletes the event, fires EventDeleted. Party::canDelete() (zero devices) is only a client-side confirm-dialog rule today - this endpoint does NOT enforce it, matching current behaviour (judgment call #4 in api-contracts-phase-c.md).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
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
    public function deleteEventv2(Request $request, $idevents): JsonResponse
    {
        $user = $request->user();
        $event = Party::findOrFail($idevents);

        if (! Fixometer::userHasEditPartyPermission($idevents, $user->id) && ! Fixometer::userIsHostOfGroup($event->group, $user->id)) {
            abort(403);
        }

        Audits::where('auditable_type', Party::class)->where('auditable_id', $idevents)->delete();
        Device::where('event', $idevents)->delete();

        // Loop-delete to avoid the gotcha where bulk delete operations don't invoke observers.
        foreach (EventsUsers::where('event', $idevents)->get() as $row) {
            $row->delete();
        }

        $event->delete();

        event(new EventDeleted($event));

        return response()->json(['data' => ['deleted' => true]]);
    }
}
