<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MapsProxyController extends Controller
{
    private function apiKey(): string
    {
        return config('GOOGLE_API_CONSOLE_KEY') ?? env('GOOGLE_API_CONSOLE_KEY', '');
    }

    /**
     * @OA\Get(
     *      path="/api/v2/maps/autocomplete",
     *      operationId="mapsAutocompletev2",
     *      tags={"Maps"},
     *      summary="Proxy for Google Places Autocomplete (keeps the API key server-side)",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="input", required=true, in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="types", required=false, in="query", @OA\Schema(type="string", default="geocode")),
     *      @OA\Response(response=200, description="Verbatim Google Places Autocomplete response"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation failure")
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v2/maps/place-details",
     *      operationId="mapsPlaceDetailsv2",
     *      tags={"Maps"},
     *      summary="Proxy for Google Places Details (keeps the API key server-side)",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="place_id", required=true, in="query", @OA\Schema(type="string")),
     *      @OA\Response(response=200, description="Verbatim Google Places Details response"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation failure")
     * )
     */
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
