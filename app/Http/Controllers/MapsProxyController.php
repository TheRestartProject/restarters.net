<?php

namespace App\Http\Controllers;

use App\Attributes\NoStory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MapsProxyController extends Controller
{
    private function apiKey(): string
    {
        return config('GOOGLE_API_CONSOLE_KEY') ?? env('GOOGLE_API_CONSOLE_KEY', '');
    }

    #[NoStory(reason: 'Server-side proxy for Google Places so the API key stays private; used by location pickers')]
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate(['input' => 'required|string']);

        $response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
            'input' => $request->input('input'),
            'types' => $request->input('types', 'geocode'),
            'key' => $this->apiKey(),
        ]);

        return response()->json($response->json());
    }

    #[NoStory(reason: 'Server-side proxy for Google Places so the API key stays private; used by location pickers')]
    public function placeDetails(Request $request): JsonResponse
    {
        $request->validate(['place_id' => 'required|string']);

        $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $request->input('place_id'),
            'fields' => 'geometry,formatted_address,address_components',
            'key' => $this->apiKey(),
        ]);

        return response()->json($response->json());
    }
}
