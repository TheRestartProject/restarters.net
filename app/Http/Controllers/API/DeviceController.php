<?php

namespace App\Http\Controllers\API;

use App\Barrier;
use App\Device;
use App\DeviceBarrier;
use App\Events\DeviceCreatedOrUpdated;
use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Notifications\AdminAbnormalDevices;
use App\Party;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Notification;
use Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller {
    /**
     * @OA\Get(
     *      path="/api/v2/devices/{id}",
     *      operationId="getDevice",
     *      tags={"Devices"},
     *      summary="Get Device",
     *      description="Returns information about a device.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Device id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                property="data",
     *                title="data",
     *                ref="#/components/schemas/Device"
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Device not found",
     *      ),
     *     )
     */

    public function getDevicev2(Request $request, $iddevices)
    {
        $device = Device::findOrFail($iddevices);

        return \App\Http\Resources\Device::make($device);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/devices",
     *      operationId="createDevice",
     *      tags={"Devices"},
     *      summary="Create Device",
     *      description="Creates a device.",
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
     *                required={"category","item_type"},
     *                @OA\Property(
     *                   property="eventid",
     *                   title="id",
     *                   description="Unique identifier of the event to which the device belongs",
     *                   format="int64",
     *                   example=1
     *                ),
     *                @OA\Property(
     *                   property="category",
     *                   ref="#/components/schemas/Device/properties/category",
     *                ),
     *                @OA\Property(
     *                   property="item_type",
     *                   ref="#/components/schemas/Device/properties/item_type",
     *                ),
     *                @OA\Property(
     *                   property="brand",
     *                   ref="#/components/schemas/Device/properties/brand",
     *                ),
     *                @OA\Property(
     *                   property="model",
     *                   ref="#/components/schemas/Device/properties/model",
     *                ),
     *                @OA\Property(
     *                   property="age",
     *                   ref="#/components/schemas/Device/properties/age",
     *                ),
     *                @OA\Property(
     *                   property="estimate",
     *                   ref="#/components/schemas/Device/properties/estimate",
     *                ),
     *                @OA\Property(
     *                   property="problem",
     *                   ref="#/components/schemas/Device/properties/problem",
     *                ),
     *                @OA\Property(
     *                   property="notes",
     *                   ref="#/components/schemas/Device/properties/notes",
     *                ),
     *                @OA\Property(
     *                    property="repair_status",
     *                    ref="#/components/schemas/Device/properties/repair_status",
     *                ),
     *                @OA\Property(
     *                    property="next_steps",
     *                    ref="#/components/schemas/Device/properties/next_steps",
     *                ),
     *                @OA\Property(
     *                     property="spare_parts",
     *                     ref="#/components/schemas/Device/properties/spare_parts",
     *                 ),
     *                @OA\Property(
     *                    property="case_study",
     *                    ref="#/components/schemas/Device/properties/case_study",
     *                ),
     *                @OA\Property(
     *                     property="barrier",
     *                     ref="#/components/schemas/Device/properties/barrier",
     *                ),
     *             )
     *         )
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Successful operation",
     *        @OA\JsonContent(
     *            @OA\Property(
     *              property="data",
     *              title="data",
     *              ref="#/components/schemas/Device"
     *            )
     *        ),
     *     )
     *  )
     */
    public function createDevicev2(Request $request)
    {
        $user = $this->getUser();

        list($eventid,
            $category,
            $item_type,
            $brand,
            $model,
            $age,
            $estimate,
            $problem,
            $notes,
            $case_study,
            $repair_status,
            $spare_parts,
            $parts_provider,
            $professional_help,
            $more_time_needed,
            $do_it_yourself,
            $barrier
        ) = $this->validateDeviceParams($request,true);

        $event = Party::findOrFail($eventid);

        if (!Fixometer::userHasEditEventsDevicesPermission($eventid, $user->id)) {
            // Only hosts can add devices to events.
            abort(403);
        }

        $data = [
            'event' => $eventid,
            'category' => $category,
            'category_creation' => $category,  // We don't expose this over the API but we record it in case it changes.
            'item_type' => $item_type,
            'brand' => $brand,
            'model' => $model,
            'age' => $age,
            'estimate' => $estimate,
            'problem' => $problem,
            'notes' => $notes,
            'wiki' => $case_study,
            'repair_status' => $repair_status,
            'spare_parts' => $spare_parts,
            'parts_provider' => $parts_provider,
            'professional_help' => $professional_help,
            'more_time_needed' => $more_time_needed,
            'do_it_yourself' => $do_it_yourself,
            'repaired_by' => $user->id,
        ];

        $device = Device::create($data);
        $idDevice = $device->iddevices;

        if ($idDevice) {
            event(new DeviceCreatedOrUpdated($device));

            if ($barrier) {
                DeviceBarrier::create([
                    'device_id' => $idDevice,
                    'barrier_id' => $barrier
                ]);
            }

            // If the number of devices exceeds set amount then notify admins.
            $deviceMiscCount = DB::table('devices')->where('category', env('MISC_CATEGORY_ID_POWERED'))->where('event', $eventid)->count() +
                DB::table('devices')->where('category', env('MISC_CATEGORY_ID_UNPOWERED'))->where('event', $eventid)->count();
            if ($deviceMiscCount == env('DEVICE_ABNORMAL_MISC_COUNT', 5)) {
                $notify_users = Fixometer::usersWhoHavePreference('admin-abnormal-devices');
                Notification::send($notify_users, new AdminAbnormalDevices([
                    'event_venue' => $event->getEventName(),
                    'event_url' => url('/party/edit/'.$eventid),
                ]));
            }
        }

        // TODO Images - probably a separate API Call.
        // We return the device and the stats to save the client another API call to update its store.

        return response()->json([
            'id' => $idDevice,
            'device' => \App\Http\Resources\Device::make($device),
            'stats' => $event->getEventStats()
        ]);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/devices/{id}",
     *      operationId="editDevice",
     *      tags={"Devices"},
     *      summary="Edit Device",
     *      description="Edits a device.",
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
     *                required={"category","item_type"},
     *                @OA\Property(
     *                   property="eventid",
     *                   title="id",
     *                   description="Unique identifier of the event to which the device belongs",
     *                   format="int64",
     *                   example=1
     *                ),
     *                @OA\Property(
     *                   property="category",
     *                   ref="#/components/schemas/Device/properties/category",
     *                ),
     *                @OA\Property(
     *                   property="item_type",
     *                   ref="#/components/schemas/Device/properties/item_type",
     *                ),
     *                @OA\Property(
     *                   property="brand",
     *                   ref="#/components/schemas/Device/properties/brand",
     *                ),
     *                @OA\Property(
     *                   property="model",
     *                   ref="#/components/schemas/Device/properties/model",
     *                ),
     *                @OA\Property(
     *                   property="age",
     *                   ref="#/components/schemas/Device/properties/age",
     *                ),
     *                @OA\Property(
     *                   property="estimate",
     *                   ref="#/components/schemas/Device/properties/estimate",
     *                ),
     *                @OA\Property(
     *                   property="problem",
     *                   ref="#/components/schemas/Device/properties/problem",
     *                ),
     *                @OA\Property(
     *                   property="notes",
     *                   ref="#/components/schemas/Device/properties/notes",
     *                ),
     *                @OA\Property(
     *                    property="repair_status",
     *                    ref="#/components/schemas/Device/properties/repair_status",
     *                ),
     *                @OA\Property(
     *                    property="next_steps",
     *                    ref="#/components/schemas/Device/properties/next_steps",
     *                ),
     *                @OA\Property(
     *                     property="spare_parts",
     *                     ref="#/components/schemas/Device/properties/spare_parts",
     *                 ),
     *                @OA\Property(
     *                    property="case_study",
     *                    ref="#/components/schemas/Device/properties/case_study",
     *                ),
     *                @OA\Property(
     *                     property="barrier",
     *                     ref="#/components/schemas/Device/properties/barrier",
     *                ),
     *             )
     *         )
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Successful operation",
     *        @OA\JsonContent(
     *            @OA\Property(
     *              property="data",
     *              title="data",
     *              ref="#/components/schemas/Device"
     *            )
     *        ),
     *     )
     *  )
     */
    public function updateDevicev2(Request $request, $iddevices)
    {
        $user = $this->getUser();

        list($eventid,
            $category,
            $item_type,
            $brand,
            $model,
            $age,
            $estimate,
            $problem,
            $notes,
            $case_study,
            $repair_status,
            $spare_parts,
            $parts_provider,
            $professional_help,
            $more_time_needed,
            $do_it_yourself,
            $barrier
            ) = $this->validateDeviceParams($request,false);

        Party::findOrFail($eventid);

        if (!Fixometer::userHasEditEventsDevicesPermission($eventid, $user->id)) {
            // Only hosts can add devices to events.
            abort(403);
        }

        $data = [
            'event' => $eventid,
            'category' => $category,
            'item_type' => $item_type,
            'brand' => $brand,
            'model' => $model,
            'age' => $age,
            'estimate' => $estimate,
            'problem' => $problem,
            'notes' => $notes,
            'wiki' => $case_study,
            'repair_status' => $repair_status,
            'spare_parts' => $spare_parts,
            'parts_provider' => $parts_provider,
            'professional_help' => $professional_help,
            'more_time_needed' => $more_time_needed,
            'do_it_yourself' => $do_it_yourself,
            'repaired_by' => $user->id,
        ];

        $device = Device::findOrFail($iddevices);
        $device->update($data);

        event(new DeviceCreatedOrUpdated($device));

        if ($barrier) {
            DeviceBarrier::updateOrCreate([
                'device_id' => $iddevices,
                'barrier_id' => $barrier
            ]);
        }

        // TODO Images - probably a separate API Call.

        return response()->json([
            'id' => $iddevices,
        ]);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/devices/{id}",
     *      operationId="deleteDevice",
     *      tags={"Devices"},
     *      summary="Delete Device",
     *      description="Deletes a device.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Device id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Device not found",
     *      ),
     *     )
     */

    public function deleteDevicev2(Request $request, $iddevices)
    {
        $user = $this->getUser();

        $device = Device::findOrFail($iddevices);

        if (!Fixometer::userHasEditEventsDevicesPermission($device->event, $user->id)) {
            // Only hosts can delete devices for events.
            abort(403);
        }

        $device->delete();

        return response()->json([
            'id' => $iddevices,
        ]);
    }

    private function validateDeviceParams(Request $request, $create): array
    {
        // We don't validate max lengths of other strings, to avoid duplicating the length information both here
        // and in the migrations.  If we wanted to do that we should extract the length dynamically from the
        // schema, which is possible but not trivial.
        if ($create) {
            $request->validate([
                'eventid' => 'required|integer',
                'item_type' => 'required|string',
                'category' => 'required|integer',
                'brand' => 'string',
                'model' => 'string',
                'age' => [ 'numeric', 'max:500' ],
                'estimate' => [ 'numeric', 'min:0' ],
                'problem' => [ 'string', 'nullable' ],
                'notes' => 'string',
                'repair_status' => [ 'string', 'in:Fixed,Repairable,End of life' ],
                'next_steps' => [ 'string', 'in:More time needed,Professional help,Do it yourself' ],
                'spare_parts' => [ 'string', 'in:No,Manufacturer,Third party' ],
                'case_study' => ['boolean'],
                'barrier' => [ 'string', 'nullable', 'in:Spare parts not available,Spare parts too expensive,No way to open the product,Repair information not available,Lack of equipment' ],
            ]);
        } else {
            $request->validate([
                'item_type' => 'required|string',
                'category' => 'required|integer',
                'brand' => 'string',
                'model' => 'string',
                'age' => [ 'numeric', 'max:500' ],
                'estimate' => [ 'numeric', 'min:0' ],
                'problem' => [ 'string', 'nullable' ],                'notes' => 'string',
                'repair_status' => [ 'string', 'in:Fixed,Repairable,End of life' ],
                'next_steps' => [ 'string', 'in:More time needed,Professional help,Do it yourself' ],
                'spare_parts' => [ 'string', 'in:No,Manufacturer,Third party' ],
                'case_study' => ['boolean'],
                'barrier' => [ 'string', 'in:Spare parts not available,Spare parts too expensive,No way to open the product,Repair information not available,Lack of equipment' ],
            ]);
        }

        $eventid = $request->input('eventid');
        $category = $request->input('category');
        $item_type = $request->input('item_type');
        $brand = $request->input('brand');
        $model = $request->input('model');
        $age = $request->input('age') || 0;
        $estimate = $request->input('estimate') || 0;
        $problem = $request->input('problem');
        $notes = $request->input('notes');
        $case_study = $request->input('case_study');
        $repair_status = $request->input('repair_status') || 0;
        $barrierInput = $request->input('barrier');

        // Our database has a slightly complex structure for historical reasons, so we need to map some input
        // values to the underlying fields.  This keeps the API clean.
        //
        // There is mirror code in Resources\Device.
        $problem = $problem ? $problem : '';
        $spare_parts = Device::SPARE_PARTS_UNKNOWN;
        $parts_provider = NULL;
        $professional_help = 0;
        $more_time_needed = 0;
        $do_it_yourself = 0;
        $barrier = 0;

        switch ($repair_status) {
            case Device::REPAIR_STATUS_FIXED_STR:
                $repair_status = Device::REPAIR_STATUS_FIXED;
                break;
            case Device::REPAIR_STATUS_REPAIRABLE_STR:
                $repair_status = Device::REPAIR_STATUS_REPAIRABLE;
                break;
            case Device::REPAIR_STATUS_ENDOFLIFE_STR:
                $repair_status = Device::REPAIR_STATUS_ENDOFLIFE;

                if (!$barrierInput) {
                    throw ValidationException::withMessages(['barrier' => ['Barrier is required for End of life devices']]);
                }

                break;
        }

        if ($barrierInput) {
            // Look up the barrier.
            $barrierEnt = Barrier::firstOrFail()->where('barrier', $barrierInput)->get();
            $barrier = $barrierEnt->toArray()[0]['id'];

            if (!$barrier) {
                throw ValidationException::withMessages(['barrier' => ['Invalid barrier supplied']]);
            }
        }

        // We can provide next_steps and spare_parts for any status - this is for recording historical information.
        if ($request->has('next_steps')) {
            switch ($request->input('next_steps')) {
                case Device::NEXT_STEPS_MORE_TIME_NEEDED_STR:
                    $more_time_needed = 1;
                    break;
                case Device::NEXT_STEPS_PROFESSIONAL_HELP_STR:
                    $professional_help = 1;
                    break;
                case Device::NEXT_STEPS_DO_IT_YOURSELF_STR:
                    $do_it_yourself = 1;
                    break;
            }
        }

        if ($request->has('spare_parts')) {
            switch ($request->input('spare_parts')) {
                case Device::PARTS_PROVIDER_NO_STR:
                    $spare_parts = Device::SPARE_PARTS_NOT_NEEDED;
                    break;
                case Device::PARTS_PROVIDER_MANUFACTURER_STR:
                    $spare_parts = Device::SPARE_PARTS_NEEDED;

                    if ($repair_status != Device::REPAIR_STATUS_ENDOFLIFE) {
                        // If it is end of life we record that the parts are needed, but there is no provider.
                        $parts_provider = Device::PARTS_PROVIDER_MANUFACTURER;
                    }
                    break;
                case Device::PARTS_PROVIDER_THIRD_PARTY_STR:
                    $spare_parts = Device::SPARE_PARTS_NEEDED;

                    if ($repair_status != Device::REPAIR_STATUS_ENDOFLIFE) {
                        // If it is end of life we record that the parts are needed, but there is no provider.
                        $parts_provider = Device::PARTS_PROVIDER_THIRD_PARTY;
                    }
                    break;
            }
        }

        return [
            $eventid,
            $category,
            $item_type,
            $brand,
            $model,
            $age,
            $estimate,
            $problem,
            $notes,
            $case_study,
            $repair_status,
            $spare_parts,
            $parts_provider,
            $professional_help,
            $more_time_needed,
            $do_it_yourself,
            $barrier
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
}