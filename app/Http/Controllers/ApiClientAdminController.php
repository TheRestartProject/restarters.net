<?php

namespace App\Http\Controllers;

use App\ApiClient;
use App\Helpers\Fixometer;
use App\Network;
use App\Services\ApiClientService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiClientAdminController extends Controller
{
    public function index()
    {
        if ($redirect = $this->redirectIfUnauthorized()) {
            return $redirect;
        }

        $networks = Network::orderBy('name', 'asc')->get();
        $networkLookup = $networks->keyBy('id');

        $clients = ApiClient::orderByDesc('active')
            ->orderBy('name', 'asc')
            ->get()
            ->each(function ($client) use ($networkLookup) {
                $client->allowed_network_names = collect($client->allowed_network_ids ?: [])
                    ->map(function ($networkId) use ($networkLookup) {
                        $network = $networkLookup->get($networkId);

                        return $network ? $network->name : null;
                    })
                    ->filter()
                    ->values()
                    ->all();
            });

        return view('admin.api-clients', [
            'clients' => $clients,
            'networks' => $networks,
        ]);
    }

    public function store(Request $request, ApiClientService $apiClientService)
    {
        if ($redirect = $this->redirectIfUnauthorized()) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'allowed_origins' => ['nullable', 'string'],
            'allowed_network_ids' => ['nullable', 'array'],
            'allowed_network_ids.*' => ['integer', 'exists:networks,id'],
            'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:10000'],
            'expires_at' => ['nullable', 'date'],
        ]);

        [$client, $plainToken] = $apiClientService->create([
            'name' => trim($validated['name']),
            'scopes' => ['events:read'],
            'allowed_origins' => $this->parseDelimitedList($validated['allowed_origins'] ?? null) ?: null,
            'allowed_network_ids' => ! empty($validated['allowed_network_ids']) ? array_map('intval', $validated['allowed_network_ids']) : null,
            'rate_limit_per_minute' => (int) $validated['rate_limit_per_minute'],
            'active' => true,
            'expires_at' => ! empty($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null,
        ]);

        return redirect()
            ->route('admin.api-clients')
            ->with('success', 'API client created.')
            ->with('generated_api_token', [
                'action' => 'created',
                'name' => $client->name,
                'token' => $plainToken,
                'token_hint' => $client->token_hint,
            ]);
    }

    public function rotate($id, ApiClientService $apiClientService)
    {
        if ($redirect = $this->redirectIfUnauthorized()) {
            return $redirect;
        }

        $client = ApiClient::findOrFail($id);
        $plainToken = $apiClientService->rotate($client);

        return redirect()
            ->route('admin.api-clients')
            ->with('success', 'API client rotated.')
            ->with('generated_api_token', [
                'action' => 'rotated',
                'name' => $client->name,
                'token' => $plainToken,
                'token_hint' => $client->token_hint,
            ]);
    }

    public function revoke($id)
    {
        if ($redirect = $this->redirectIfUnauthorized()) {
            return $redirect;
        }

        $client = ApiClient::findOrFail($id);
        $client->active = false;
        $client->save();

        return redirect()
            ->route('admin.api-clients')
            ->with('success', 'API client revoked.');
    }

    private function parseDelimitedList($value): array
    {
        if (! $value) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $value))));
    }

    private function redirectIfUnauthorized()
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        return null;
    }
}
