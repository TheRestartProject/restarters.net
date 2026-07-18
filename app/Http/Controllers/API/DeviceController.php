<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use App\Barrier;
use App\Device;
use App\DeviceBarrier;
use App\Events\DeviceCreatedOrUpdated;
use App\Helpers\Fixometer;
use App\Helpers\Tus;
use App\Http\Controllers\Controller;
use App\Notifications\AdminAbnormalDevices;
use App\Party;
use App\User;
use App\Xref;
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
            $repair_status,
            $spare_parts,
            $parts_provider,
            $professional_help,
            $more_time_needed,
            $do_it_yourself,
            $barrier
        ) = $this->validateDeviceParams($request,true);

        // We may have uploaded photos before creation, and we have a draft id which allows us to patch that
        // up.
        $draftId = $request->input('id');

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

            // We might have some photos uploaded for this device.  Record them against this device instance.
            // Each instance of a device shares the same underlying photo file.
            $File = new \FixometerFile;
            $images = $File->findImages(env('TBL_DEVICES'), $draftId);
            foreach ($images as $image) {
                $xref = Xref::findOrFail($image->idxref);
                $xref->copy($idDevice);
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
    public function updateDevicev2(Request $request, $iddevices): JsonResponse
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
            $repair_status,
            $spare_parts,
            $parts_provider,
            $professional_help,
            $more_time_needed,
            $do_it_yourself,
            $barrier
            ) = $this->validateDeviceParams($request,false);

        $event = Party::findOrFail($eventid);

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
            'repair_status' => $repair_status,
            'spare_parts' => $spare_parts,
            'parts_provider' => $parts_provider,
            'professional_help' => $professional_help,
            'more_time_needed' => $more_time_needed,
            'do_it_yourself' => $do_it_yourself,
            'repaired_by' => $user->id,
        ];

        $device = Device::findOrFail($iddevices);

        // IDOR guard: the permission check above validated the *target* eventid
        // from the request body, but the device is loaded by its URL id. Also
        // require edit permission on the device's *current* owning event -
        // otherwise a host of event A could pass eventid=A and reassign/
        // overwrite a device that actually belongs to someone else's event B
        // (deleteDevicev2 derives the event from the device for the same reason).
        if (!Fixometer::userHasEditEventsDevicesPermission($device->event, $user->id)) {
            abort(403);
        }

        $device->update($data);

        event(new DeviceCreatedOrUpdated($device));

        if ($barrier) {
            // We only allow one barrier now, so delete any old ones.
            DeviceBarrier::where('device_id', $iddevices)->delete();
            DeviceBarrier::create([
                'device_id' => $iddevices,
                'barrier_id' => $barrier
            ]);
        }

        return response()->json([
            'id' => $iddevices,
            'device' => \App\Http\Resources\Device::make($device),
            'stats' => $event->getEventStats()
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

    public function deleteDevicev2(Request $request, $iddevices): JsonResponse
    {
        $user = $this->getUser();

        $device = Device::findOrFail($iddevices);
        $eventid = $device->event;

        if (!Fixometer::userHasEditEventsDevicesPermission($device->event, $user->id)) {
            // Only hosts can delete devices for events.
            abort(403);
        }

        $device->delete();

        $event = Party::findOrFail($eventid);

        return response()->json([
            'id' => $iddevices,
            'stats' => $event->getEventStats()
        ]);
    }

    private function validateDeviceParams(Request $request, $create): array
    {
        // We don't validate max lengths of other strings, to avoid duplicating the length information both here
        // and in the migrations.  If we wanted to do that we should extract the length dynamically from the
        // schema, which is possible but not trivial.
        $request->validate([
            'eventid' => 'required|integer',
            'item_type' => 'string',  // Some of the tests, at least, treat this as optional.
            'category' => 'required|integer',
            'brand' => 'string',
            'model' => 'string',
            'age' => [ 'numeric', 'max:500' ],
            'estimate' => [ 'numeric', 'min:0' ],
            'problem' => [ 'string', 'nullable' ],
            'notes' => 'string',
            'repair_status' => [ 'string', 'in:Fixed,Repairable,End of life' ],
            'next_steps' => [ 'string', 'in:More time needed,Professional help,Do it yourself', 'nullable' ],
            'spare_parts' => [ 'string', 'in:No,Manufacturer,Third party' ],
            'barrier' => [ 'string', 'nullable', 'in:Spare parts not available,Spare parts too expensive,No way to open the product,Repair information not available,Lack of equipment' ],
        ]);

        $eventid = $request->input('eventid');
        $category = $request->input('category');
        $item_type = $request->input('item_type');
        $brand = $request->input('brand');
        $model = $request->input('model');
        $age = $request->input('age');
        $age = $age ? $age : 0;
        $estimate = $request->input('estimate');
        $estimate = $estimate ? $estimate : 0;
        $problem = $request->input('problem');
        $notes = $request->input('notes');
        $repair_status = $request->input('repair_status');
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
                break;
            default:
                $repair_status = 0;
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
            // SPA bearer tokens authenticate via the sanctum guard.
            $user = auth('sanctum')->user();
        }

        if (!$user) {
            $user = auth('api')->user();
        }

        if (!$user) {
            throw new AuthenticationException();
        }

        return $user;
    }

    /**
     * @OA\Post(
     *      path="/api/v2/devices/{id}/images",
     *      operationId="uploadDeviceImagev2",
     *      tags={"Devices"},
     *      summary="Attach a completed tus upload as a device photo",
     *      description="Mirrors GroupMembershipController::uploadImagev2 and EventAttendanceController::uploadImagev2 (design §5 point 11: extend PR #868's tus pattern to device images too) - upload the file to /api/tus first, then attach it here by upload_key. Devices support multiple photos, so an upload never clears previous ones. Permission: userHasEditEventsDevicesPermission for the device's event.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(required={"upload_key"}, @OA\Property(property="upload_key", type="string"))
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Image attached",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="image_url", type="string")
     *          ))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Not permitted to edit this device, or data consent required"),
     *      @OA\Response(response=404, description="Device not found"),
     *      @OA\Response(response=422, description="Missing/expired/oversized/non-image upload")
     * )
     */
    public function uploadImagev2(Request $request, $iddevices): JsonResponse
    {
        $user = $request->user();
        $device = Device::findOrFail($iddevices);

        if (!Fixometer::userHasEditEventsDevicesPermission($device->event, $user->id)) {
            abort(403);
        }

        $validated = $request->validate([
            'upload_key' => 'required|string',
        ]);

        $filePath = EventAttendanceController::validatedTusFilePath($validated['upload_key'], 'devices');

        $file = new \FixometerFile();
        // $clear=false: devices support multiple photos, unlike a group/profile picture.
        $filename = $file->uploadLocalFile($filePath, 'image', $device->iddevices, env('TBL_DEVICES'), false, true, false);

        $cache = Tus::buildCache();
        $cache->delete($validated['upload_key']);
        @unlink($filePath);

        if (! $filename) {
            throw ValidationException::withMessages([
                'upload_key' => [__('devices.image_upload_error')],
            ]);
        }

        return response()->json([
            'data' => [
                'image_url' => url('/uploads/mid_'.$filename),
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/devices/{id}/images/{idimages}",
     *      operationId="deleteDeviceImagev2",
     *      tags={"Devices"},
     *      summary="Detach a photo from a device",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="idimages", description="The xref id linking the image to the device", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Deleted",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="deleted", type="boolean")
     *          ))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Not permitted to edit this device, or data consent required"),
     *      @OA\Response(response=404, description="Device or image not found")
     * )
     */
    public function deleteImagev2(Request $request, $iddevices, $idimages): JsonResponse
    {
        $user = $request->user();
        $device = Device::findOrFail($iddevices);

        if (!Fixometer::userHasEditEventsDevicesPermission($device->event, $user->id)) {
            abort(403);
        }

        $xref = Xref::where('idxref', $idimages)
            ->where('reference', $device->iddevices)
            ->where('reference_type', env('TBL_DEVICES'))
            ->first();

        if (! $xref) {
            abort(404, 'Image not found for this device.');
        }

        $xref->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/devices/options",
     *      operationId="getDeviceOptionsv2",
     *      tags={"Devices"},
     *      summary="Hard-coded device option lists: barriers, spare parts, next steps",
     *      description="Item-type autocomplete/category suggestion is GET /api/v2/items and brands is GET /api/v2/brands (both already exist and are reused, not duplicated here). spare_parts/next_steps have no table - hard-coded the same way Device::REPAIR_STATUS_*_STR constants already are.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="barriers", type="array", @OA\Items(
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="name", type="string")
     *              )),
     *              @OA\Property(property="spare_parts", type="array", @OA\Items(type="string")),
     *              @OA\Property(property="next_steps", type="array", @OA\Items(type="string"))
     *          ))
     *      )
     * )
     */
    public function optionsv2(): JsonResponse
    {
        return response()->json([
            'data' => [
                'barriers' => Barrier::all()->map(fn ($b) => ['id' => $b->id, 'name' => $b->barrier])->values()->all(),
                'spare_parts' => [
                    Device::PARTS_PROVIDER_NO_STR,
                    Device::PARTS_PROVIDER_MANUFACTURER_STR,
                    Device::PARTS_PROVIDER_THIRD_PARTY_STR,
                ],
                'next_steps' => [
                    Device::NEXT_STEPS_MORE_TIME_NEEDED_STR,
                    Device::NEXT_STEPS_PROFESSIONAL_HELP_STR,
                    Device::NEXT_STEPS_DO_IT_YOURSELF_STR,
                ],
            ],
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/devices",
     *      operationId="listDevicesv2",
     *      tags={"Devices"},
     *      summary="List/search devices, paginated",
     *      description="v2 equivalent of GET /api/devices/{page}/{size} (v1, kept for the legacy client). Read ApiController::getDevices() for the query builder this mirrors - same filters, same join/LIKE semantics, same batch-loaded images.",
     *      @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *      @OA\Parameter(name="size", in="query", @OA\Schema(type="integer", default=20)),
     *      @OA\Parameter(name="sortBy", in="query", @OA\Schema(type="string", default="event_start_utc")),
     *      @OA\Parameter(name="sortDesc", in="query", @OA\Schema(type="string", default="DESC")),
     *      @OA\Parameter(name="powered", in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="category", in="query", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="brand", in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="model", in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="item_type", in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="status", in="query", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="comments", in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="wiki", in="query", @OA\Schema(type="boolean")),
     *      @OA\Parameter(name="group", in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="from_date", in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="to_date", in="query", @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="count", type="integer"),
     *              @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/Device"))
     *          ))
     *      )
     * )
     */
    public function listDevicesv2(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $size = max(1, (int) $request->input('size', 20));
        $sortBy = $request->input('sortBy', 'event_start_utc');
        $sortDesc = $request->input('sortDesc', 'DESC');
        $powered = $request->input('powered');
        $category = $request->input('category');
        $brand = $request->input('brand');
        $model = $request->input('model');
        $item_type = $request->input('item_type');
        $status = $request->input('status');
        $comments = $request->input('comments');
        $wiki = filter_var($request->input('wiki', false), FILTER_VALIDATE_BOOLEAN);
        $group = $request->input('group');
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');

        // Same filter set as v1 ApiController::getDevices(), including its "powered defaults to
        // unpowered when the param is absent" quirk - preserved for parity, not a new decision.
        $wheres = [
            ['categories.powered', '=', $powered == 'true' ? 1 : 0],
        ];

        if ($category) {
            $wheres[] = ['idcategories', '=', $category];
        }

        if ($brand) {
            $wheres[] = ['devices.brand', 'LIKE', '%'.$brand.'%'];
        }

        if ($model) {
            $wheres[] = ['devices.model', 'LIKE', '%'.$model.'%'];
        }

        if ($item_type) {
            $wheres[] = ['devices.item_type', 'LIKE', '%'.$item_type.'%'];
        }

        if ($comments) {
            $wheres[] = ['devices.problem', 'LIKE', '%'.$comments.'%'];
        }

        if ($wiki) {
            $wheres[] = ['devices.wiki', '=', 1];
        }

        if ($status) {
            $wheres[] = ['repair_status', '=', $status];
        }

        if ($group) {
            $wheres[] = ['groups.name', 'LIKE', '%'.$group.'%'];
        }

        if ($from_date) {
            $wheres[] = ['events.event_start_utc', '>=', $from_date];
        }

        if ($to_date) {
            $wheres[] = ['events.event_end_utc', '<=', $to_date];
        }

        $query = Device::with(['deviceEvent.theGroup', 'deviceCategory', 'barriers'])
            ->join('events', 'events.idevents', '=', 'devices.event')
            ->join('groups', 'events.group', '=', 'groups.idgroups')
            ->join('categories', 'devices.category', '=', 'categories.idcategories')
            ->where($wheres)
            ->orderBy($sortBy, $sortDesc);

        $count = $query->count();

        $items = $query->skip(($page - 1) * $size)
            ->take($size)
            ->get();

        // Batch-load device images to avoid N+1 per device.
        $device_ids = $items->pluck('iddevices')->toArray();
        $allImages = (new \FixometerFile)->findImagesForMany(env('TBL_DEVICES'), $device_ids);
        foreach ($items as $item) {
            $item->preloadedImages = $allImages[$item->iddevices] ?? [];
        }

        $item_data = [];
        foreach ($items as $item) {
            $item_data[] = (new \App\Http\Resources\Device($item))->resolve();
        }

        return response()->json([
            'data' => [
                'count' => $count,
                'items' => $item_data,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/stats/latest-repaired-event",
     *      operationId="getLatestRepairedEventv2",
     *      tags={"Devices"},
     *      summary="Most recent finished event with at least one repaired device",
     *      description="Public. Mirrors the inline query in the legacy DeviceController::index() (fixometer home page banner) - Party::with('theGroup')->hasDevicesRepaired(1)->eventHasFinished()->orderBy('event_start_utc','DESC')->first().",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", type="object", nullable=true,
     *              @OA\Property(property="id", type="integer"),
     *              @OA\Property(property="waste_prevented", type="number"),
     *              @OA\Property(property="group", ref="#/components/schemas/GroupSummary")
     *          ))
     *      )
     * )
     */
    public function latestRepairedEventv2(): JsonResponse
    {
        $event = Party::with('theGroup')
            ->hasDevicesRepaired(1)
            ->eventHasFinished()
            ->orderBy('event_start_utc', 'DESC')
            ->first();

        if (! $event) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'id' => $event->idevents,
                'waste_prevented' => $event->waste_prevented,
                'group' => \App\Http\Resources\GroupSummary::make($event->theGroup),
            ],
        ]);
    }
}