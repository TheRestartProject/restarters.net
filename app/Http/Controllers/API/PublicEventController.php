<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Http\Controllers\Controller;
use App\Http\Resources\GroupLocation;
use App\Party;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicEventController extends Controller
{
    public function listEvents(Request $request): JsonResponse
    {
        return $this->listWithFilters($request);
    }

    public function showEvent(Request $request, $id): JsonResponse
    {
        $query = $this->buildBaseEventQuery();
        $this->applyClientRestrictions($query, $request);

        $event = $query->where('events.idevents', $id)->firstOrFail();

        return response()->json([
            'data' => $this->toPublicEventArray($event),
        ]);
    }

    public function listGroupEvents(Request $request, $id): JsonResponse
    {
        Group::findOrFail($id);

        return $this->listWithFilters($request, function (Builder $query) use ($id) {
            $query->where('events.group', $id);
        });
    }

    private function listWithFilters(Request $request, ?callable $filter = null): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date'],
            'updated_start' => ['nullable', 'date'],
            'updated_end' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $this->buildBaseEventQuery();
        $this->applyClientRestrictions($query, $request);

        if ($filter) {
            $filter($query);
        }

        $this->applyDateFilters($query, $validated);

        $maxUpdatedAt = (clone $query)->max('events.updated_at');

        $perPage = (int) ($validated['per_page'] ?? 50);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(function (Party $event) {
                return $this->toPublicEventArray($event);
            })->values(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'sync' => [
                'generated_at' => Carbon::now()->toIso8601String(),
                'max_updated_at' => $maxUpdatedAt ? Carbon::parse($maxUpdatedAt)->toIso8601String() : null,
            ],
        ]);
    }

    private function buildBaseEventQuery(): Builder
    {
        return Party::query()
            ->with(['theGroup.groupImage.image'])
            ->join('groups', 'groups.idgroups', '=', 'events.group')
            ->whereNull('events.deleted_at')
            ->where('events.approved', true)
            ->where('groups.approved', true)
            ->whereNull('groups.archived_at')
            ->distinct()
            ->select('events.*')
            ->orderBy('events.event_start_utc', 'asc');
    }

    private function applyClientRestrictions(Builder $query, Request $request): void
    {
        $client = $request->attributes->get('apiClient');
        $allowedNetworkIds = $client ? ($client->allowed_network_ids ?: []) : [];

        if (! empty($allowedNetworkIds)) {
            $query->join('group_network as permitted_network', 'permitted_network.group_id', '=', 'groups.idgroups')
                ->whereIn('permitted_network.network_id', $allowedNetworkIds);
        }
    }

    private function applyDateFilters(Builder $query, array $validated): void
    {
        if (! empty($validated['start'])) {
            $query->where('events.event_start_utc', '>=', Carbon::parse($validated['start'])->setTimezone('UTC')->toIso8601String());
        } else {
            $query->where('events.event_end_utc', '>=', Carbon::now()->setTimezone('UTC')->toIso8601String());
        }

        if (! empty($validated['end'])) {
            $query->where('events.event_end_utc', '<=', Carbon::parse($validated['end'])->setTimezone('UTC')->toIso8601String());
        }

        if (! empty($validated['updated_start'])) {
            $query->where('events.updated_at', '>=', Carbon::parse($validated['updated_start'])->setTimezone('UTC')->toDateTimeString());
        }

        if (! empty($validated['updated_end'])) {
            $query->where('events.updated_at', '<=', Carbon::parse($validated['updated_end'])->setTimezone('UTC')->toDateTimeString());
        }
    }

    private function toPublicEventArray(Party $event): array
    {
        $group = $event->theGroup;

        $data = [
            'id' => $event->idevents,
            'start' => $event->event_start_utc,
            'end' => $event->event_end_utc,
            'timezone' => $event->timezone,
            'title' => $event->venue ?? $event->location,
            'location' => $event->location,
            'online' => (bool) $event->online,
            'lat' => $event->latitude,
            'lng' => $event->longitude,
            'group' => [
                'id' => $group->idgroups,
                'name' => $group->name,
                'image' => $group->groupImage && is_object($group->groupImage) && is_object($group->groupImage->image)
                    ? $group->groupImage->image->path
                    : null,
                'location' => GroupLocation::make($group)->resolve(),
                'updated_at' => Carbon::parse($group->updated_at)->toIso8601String(),
                'archived_at' => $group->archived_at ? Carbon::parse($group->archived_at)->toIso8601String() : null,
                'summary' => true,
            ],
            'description' => $event->free_text,
            'updated_at' => Carbon::parse($event->updated_at)->toIso8601String(),
            'approved' => (bool) $event->approved,
            'full' => true,
        ];

        if ($event->link) {
            $data['link'] = $event->link;
        }

        return $data;
    }
}
