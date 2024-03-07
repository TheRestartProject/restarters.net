<?php

namespace App\Http\Controllers\API;

use App\Barrier;
use App\Device;
use App\DeviceBarrier;
use App\Events\DeviceCreatedOrUpdated;
use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Party;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Notification;
use Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
     *          description="Event not found",
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

        list($partyid,
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

        Party::findOrFail($partyid);

        if (!Fixometer::userHasEditEventsDevicesPermission($partyid, $user->id)) {
            // Only hosts can add devices to events.
            abort(403);
        }

        $data = [
            'event' => $partyid,
            'category' => $category,
            'category_creation' => $category,
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
        }

        // TODO Images - probably a separate API Call.

        return response()->json([
            'id' => $idDevice,
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
                'problem' => 'string',
                'notes' => 'string',
                'repair_status' => [ 'string', 'in:Fixed,Repairable,End-of-life' ],
                'next_steps' => [ 'string', 'in:More time needed,Professional help,Do it yourself' ],
                'spare_parts' => [ 'string', 'in:No,Manufacturer,Third party' ],
                'case_study' => ['boolean'],
                'barrier' => [ 'string', 'in:Spare parts not available,Spare parts too expensive,No way to open the product,Repair information not available,Lack of equipment' ],
            ]);
        } else {
            $request->validate([
                'item_type' => 'required|string',
                'category' => 'required|integer',
                'brand' => 'string',
                'model' => 'string',
                'age' => [ 'numeric', 'max:500' ],
                'estimate' => [ 'numeric', 'min:0' ],
                'problem' => 'string',
                'notes' => 'string',
                'repair_status' => [ 'string', 'in:Fixed,Repairable,End-of-life' ],
                'next_steps' => [ 'string', 'in:More time needed,Professional help,Do it yourself' ],
                'spare_parts' => [ 'string', 'in:No,Manufacturer,Third party' ],
                'case_study' => ['boolean'],
                'barrier' => [ 'string', 'in:Spare parts not available,Spare parts too expensive,No way to open the product,Repair information not available,Lack of equipment' ],
            ]);
        }

        $partyid = $request->input('eventid');
        $category = $request->input('category');
        $item_type = $request->input('item_type');
        $brand = $request->input('brand');
        $model = $request->input('model');
        $age = $request->input('age');
        $estimate = $request->input('estimate');
        $problem = $request->input('problem');
        $notes = $request->input('notes');
        $case_study = $request->input('case_study');

        // Our database has a slightly complex structure for historical reasons, so we need to map some input
        // values to the underlying fields.  This keeps the API clean.
        //
        // There is mirror code in Resources\Device.
        $spare_parts = Device::SPARE_PARTS_UNKNOWN;
        $parts_provider = NULL;
        $professional_help = 0;
        $more_time_needed = 0;
        $do_it_yourself = 0;
        $barrier = 0;

        switch ($request->input('repair_status')) {
            case 'Fixed':
                $repair_status = Device::REPAIR_STATUS_FIXED;
                break;
            case 'Repairable':
                switch ($request->input('next_steps')) {
                    case 'More time needed':
                        $more_time_needed = 1;
                        break;
                    case 'Professional help':
                        $professional_help = 1;
                        break;
                    case 'Do it yourself':
                        $do_it_yourself = 1;
                        break;
                }

                switch ($request->input('spare_parts')) {
                    case 'No':
                        $spare_parts = Device::SPARE_PARTS_NOT_NEEDED;
                        break;
                    case 'Manufacturer':
                        $spare_parts = Device::SPARE_PARTS_NEEDED;
                        $parts_provider = Device::PARTS_PROVIDER_MANUFACTURER;
                        break;
                    case 'Third party':
                        $spare_parts = Device::SPARE_PARTS_NEEDED;
                        $parts_provider = Device::PARTS_PROVIDER_THIRD_PARTY;
                        break;
                }

                $repair_status = Device::REPAIR_STATUS_REPAIRABLE;
                break;
            case 'End of life':
                $repair_status = Device::REPAIR_STATUS_ENDOFLIFE;

                // Look up the barrier.
                $barrier = Barrier::firstOrFail()->where('barrier', $request->input('barrier'))->id;
                break;
        }

        return array(
            $partyid,
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
        );
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