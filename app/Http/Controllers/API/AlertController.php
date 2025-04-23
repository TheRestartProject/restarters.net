<?php

namespace App\Http\Controllers\API;

use App\Models\Alert;
use App\Http\Controllers\Controller;
use App\Http\Resources\AlertCollection;
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
    public function listAlertsv2(Request $request) {
        // Alerts don't change often, so we can cache them.
        if (\Cache::has('alerts')) {
            $alerts = \Cache::get('alerts');
        } else {
            $now = Carbon::now()->setTimezone('UTC')->toDateTimeString();
            $alerts = Alert::where('start', '<=', $now)->where('end', '>=', $now)->get();
            \Cache::put('alerts', $alerts, 7200);
        }

        return [
            'data' => AlertCollection::make($alerts)
        ];
    }

    /**
     * @OA\Put(
     *      path="/api/v2/alerts",
     *      operationId="createAlert",
     *      tags={"Alerts"},
     *      summary="Create Alert",
     *      description="Creates an alert.",
     *      @OA\Parameter(
     *          name="api_token",
     *          description="A valid user API token",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="1234"
     *          )
     *      ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                required={"title","html","start","end"},
     *                @OA\Property(
     *                   property="title",
     *                   ref="#/components/schemas/Alert/properties/title",
     *                ),
     *                @OA\Property(
     *                   property="html",
     *                   ref="#/components/schemas/Alert/properties/html",
     *                ),
     *                @OA\Property(
     *                   property="start",
     *                   ref="#/components/schemas/Alert/properties/start",
     *                ),
     *                @OA\Property(
     *                   property="end",
     *                   ref="#/components/schemas/Alert/properties/end",
     *                ),
     *                @OA\Property(
     *                   property="ctatitle",
     *                   ref="#/components/schemas/Alert/properties/ctalink",
     *                ),
     *                @OA\Property(
     *                   property="ctalink",
     *                   ref="#/components/schemas/Alert/properties/ctalink",
     *                ),
     *             )
     *         )
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Successful operation",
     *        @OA\JsonContent(
     *            @OA\Property(
     *              property="id",
     *              title="id",
     *              ref="#/components/schemas/Alert/properties/id"
     *            )
     *        ),
     *     )
     *  )
     */
    public function addAlertv2(Request $request)
    {
        $user = $this->getUser();

        if (!$user->hasRole('Administrator')) {
            throw new AuthenticationException();
        }

        list($start, $end, $title, $html, $ctatitle, $ctalink) = $this->validateAlertParams($request, true);

        $id = Alert::Create([
            'start' => $start,
            'end' => $end,
            'title' => $title,
            'html' => $html,
            'ctatitle' => $ctatitle,
            'ctalink' => $ctalink
        ])->id;

        return [
          'id' => $id
        ];
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/alerts/{id}",
     *      operationId="updateAlert",
     *      tags={"Alerts"},
     *      summary="Edit Alert",
     *      description="Edits an alert.",
     *      @OA\Parameter(
     *          name="api_token",
     *          description="A valid user API token",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="1234"
     *          )
     *      ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                required={"title","html","start","end"},
     *                @OA\Property(
     *                   property="title",
     *                   ref="#/components/schemas/Alert/properties/title",
     *                ),
     *                @OA\Property(
     *                   property="html",
     *                   ref="#/components/schemas/Alert/properties/html",
     *                ),
     *                @OA\Property(
     *                   property="start",
     *                   ref="#/components/schemas/Alert/properties/start",
     *                ),
     *                @OA\Property(
     *                   property="end",
     *                   ref="#/components/schemas/Alert/properties/end",
     *                ),
     *                @OA\Property(
     *                   property="ctatitle",
     *                   ref="#/components/schemas/Alert/properties/ctalink",
     *                ),
     *                @OA\Property(
     *                   property="ctalink",
     *                   ref="#/components/schemas/Alert/properties/ctalink",
     *                ),
     *             )
     *         )
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Successful operation",
     *        @OA\JsonContent(
     *            @OA\Property(
     *              property="id",
     *              title="id",
     *              ref="#/components/schemas/Alert/properties/id"
     *            )
     *        ),
     *     )
     *  )
     */
    public function updateAlertv2(Request $request, $id)
    {
        $user = $this->getUser();

        if (!$user->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $alert = Alert::findOrFail($id);

        list($start, $end, $title, $html, $ctatitle, $ctalink) = $this->validateAlertParams($request, true);

        $alert->update([
            'start' => $start,
            'end' => $end,
            'title' => $title,
            'html' => $html,
            'ctatitle' => $ctatitle,
            'ctalink' => $ctalink
        ]);

        \Cache::clear('alerts');

        return [
            'id' => $id
        ];
    }

    private function getUser()
    {
        // We want to allow this call to work if a) we are logged in as a user, or b) we have a valid API token.
        //
        // This is a slightly odd thing to do, but it is necessary to get both the PHPUnit tests and the
        // real client use of the API to work.
        $user = Auth::user();

        if (!$user) {
            $user = auth('api')->user();
        }

        if (!$user) {
            throw new AuthenticationException();
        }

        return $user;
    }

    private function validateAlertParams(Request $request, $create): array
    {
        if ($create) {
            $request->validate([
                'start' => ['required', 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z'],
                'end' => ['required', 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z'],
                'title' => ['required', 'max:255'],
                'html' => ['required'],
                'ctatitle' => ['nullable', 'max:255'],
                'ctalink' => ['nullable', 'url'],
            ]);
        } else {
            $request->validate([
                'id' => 'required|integer',
                'start' => ['required', 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z'],
                'end' => ['required', 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z'],
                'title' => ['required', 'max:255'],
                'html' => ['required'],
                'ctatitle' => ['nullable', 'max:255'],
                'ctalink' => ['nullable', 'url'],
            ]);
        }

        $start = $request->input('start');
        $end = $request->input('end');
        $title = $request->input('title');
        $html = $request->input('html');
        $ctatitle = $request->input('ctatitle', null);
        $ctalink = $request->input('ctalink', null);

        return [
            $start,
            $end,
            $title,
            $html,
            $ctatitle,
            $ctalink
        ];
    }
}
