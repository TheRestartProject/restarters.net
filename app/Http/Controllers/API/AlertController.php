<?php

namespace App\Http\Controllers\API;

use App\Alert;
use App\Http\Controllers\Controller;
use App\Http\Resources\ItemCollection;
use Auth;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Notification;
use Illuminate\Validation\ValidationException;

class AlertController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v2/alerts",
     *      operationId="listAlertsv2",
     *      tags={"Alerts"},
     *      summary="Get list of active alerts",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                property="data",
     *                title="data",
     *                description="An array of alerts",
     *                type="array",
     *                @OA\Items(
     *                   ref="#/components/schemas/Alert"
     *                )
     *             )
     *          )
     *       ),
     *     )
     */
    public static function listAlertsv2(Request $request) {
        // Alerts don't change often, so we can cache them.
        if (\Cache::has('alerts')) {
            $items = \Cache::get('alerts');
        } else {
            $items = Alert::whereDate('start', '>=', date('Y-m-d  H:i', strtotime(Carbon::now())))
                ->whereDate('end', '<=', date('Y-m-d  H:i', strtotime(Carbon::now())))->get();
            \Cache::put('alerts', $items, 7200);
        }

        return [
            'data' => ItemCollection::make($items)
        ];
    }
}
