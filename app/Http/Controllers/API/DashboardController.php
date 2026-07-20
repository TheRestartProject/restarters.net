<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Http\Controllers\Controller;
use App\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The SPA's replacement for DashboardController@index's Blade props: one call
 * returning the current user's groups, nearby groups, and upcoming events.
 */
class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v2/dashboard",
     *      operationId="getDashboardv2",
     *      tags={"Dashboard"},
     *      summary="Dashboard data for the current user: their groups, nearby groups and upcoming events",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Dashboard data",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="has_location", type="boolean", description="Whether the user has a latitude/longitude set"),
     *                  @OA\Property(property="your_groups", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="role", type="integer"),
     *                      @OA\Property(property="archived", type="boolean"),
     *                      @OA\Property(property="image_url", type="string", nullable=true)
     *                  )),
     *                  @OA\Property(property="nearby_groups", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="distance", type="number"),
     *                      @OA\Property(property="location", type="string", nullable=true),
     *                      @OA\Property(property="image_url", type="string", nullable=true)
     *                  )),
     *                  @OA\Property(property="new_nearby_groups", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="distance", type="number"),
     *                      @OA\Property(property="location", type="string", nullable=true),
     *                      @OA\Property(property="image_url", type="string", nullable=true)
     *                  )),
     *                  @OA\Property(property="upcoming_events", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="title", type="string", nullable=true),
     *                      @OA\Property(property="start", type="string", format="date-time"),
     *                      @OA\Property(property="end", type="string", format="date-time"),
     *                      @OA\Property(property="timezone", type="string", nullable=true),
     *                      @OA\Property(property="online", type="boolean"),
     *                      @OA\Property(property="location", type="string", nullable=true),
     *                      @OA\Property(property="attending", type="boolean"),
     *                      @OA\Property(property="group", type="object", nullable=true,
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="name", type="string")
     *                      )
     *                  ))
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function indexv2(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'has_location' => ! is_null($user->latitude) && ! is_null($user->longitude),
                'your_groups' => self::yourGroups($user),
                'nearby_groups' => self::expandNearbyGroups($user->groupsNearby(2)),
                'new_nearby_groups' => self::expandNearbyGroups($user->groupsNearby(3, '1 month ago')),
                'upcoming_events' => self::upcomingEvents($user),
            ],
        ]);
    }

    private static function yourGroups($user): array
    {
        // Groups where this user has a (not-deleted) users_groups pivot row. Eager-load the
        // group image relation to avoid an N+1 query per group when building image_url below.
        $groups = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->where('users_groups.user', $user->id)
            ->whereNull('users_groups.deleted_at')
            ->orderBy('groups.name', 'ASC')
            ->groupBy('groups.idgroups', 'groups.name', 'users_groups.role', 'groups.archived_at')
            ->select(['groups.idgroups', 'groups.name', 'users_groups.role', 'groups.archived_at'])
            ->take(5)
            ->with('groupImage.image')
            ->get();

        return $groups->map(function ($group) {
            return [
                'id' => $group->idgroups,
                'name' => $group->name,
                'role' => (int) $group->role,
                'archived' => ! is_null($group->archived_at),
                'image_url' => $group->realImageUrl(),
            ];
        })->values()->all();
    }

    private static function expandNearbyGroups(iterable $groups): array
    {
        $ret = [];

        foreach ($groups as $group) {
            $ret[] = $group->toNearbySummary();
        }

        return $ret;
    }

    private static function upcomingEvents($user): array
    {
        $events = Party::futureForUser()->with('theGroup')->take(5)->get();

        $ret = [];

        foreach ($events as $event) {
            $group = $event->theGroup;

            $ret[] = [
                'id' => $event->idevents,
                'title' => $event->venue ?? $event->location,
                'start' => $event->event_start_utc,
                'end' => $event->event_end_utc,
                'timezone' => $event->timezone,
                'online' => (bool) $event->online,
                'location' => $event->location,
                'attending' => $event->isBeingAttendedBy($user->id),
                'group' => $group ? [
                    'id' => $group->idgroups,
                    'name' => $group->name,
                ] : null,
            ];
        }

        return $ret;
    }
}
