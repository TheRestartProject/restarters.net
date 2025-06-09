<?php

namespace App\Http\Controllers\API;

use App\Device;
use App\Http\Controllers\Controller;
use App\Http\Resources\ItemCollection;
use Auth;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Notification;
use Illuminate\Validation\ValidationException;

class ItemController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v2/items",
     *      operationId="listItemsv2",
     *      tags={"Items"},
     *      summary="Get suggested list of items which could be used in a Device.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                property="data",
     *                title="data",
     *                description="An array of items",
     *                type="array",
     *                @OA\Items(
     *                   ref="#/components/schemas/Item"
     *                )
     *             )
     *          )
     *       ),
     *     )
     */
    public static function listItemsv2(Request $request) {
        // Item types don't change often, so we can cache them.
        // Allow cache refresh for testing purposes
        $refreshCache = $request->has('refresh_cache') && $request->get('refresh_cache') === 'true';
        
        if ($refreshCache) {
            \Cache::forget('item_types');
        }
        
        if (\Cache::has('item_types')) {
            $items = \Cache::get('item_types');
        } else {
            $items = Device::getItemTypes();
            \Cache::put('item_types', $items, 7200);
        }

        return [
            'data' => ItemCollection::make($items)
        ];
    }
}
