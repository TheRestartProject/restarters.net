<?php

namespace App\Http\Controllers;

use App\Brands;
use App\Cluster;
use App\Device;
use App\Events\DeviceCreatedOrUpdated;
use App\EventsUsers;
use App\Group;
use App\Helpers\Fixometer;
use App\Notifications\AdminAbnormalDevices;
use App\Party;
use App\User;
use App\UserGroups;
use App\Xref;
use Auth;
use FixometerFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Lang;
use Notification;
use View;

class DeviceController extends Controller
{
    public function index($search = null)
    {
        $user = User::getProfile(Auth::id());
        $clusters = Cluster::with(['categories'])->get()->all();
        $brands = Brands::orderBy('brand_name', 'asc')->get()->all();

        $most_recent_finished_event = Party::with('theGroup')
        ->hasDevicesRepaired(1)
        ->eventHasFinished()
        ->orderBy('event_start_utc', 'DESC')
        ->first();

        if ($most_recent_finished_event) {
            $most_recent_finished_event['id_events'] = $most_recent_finished_event->idevents;
            $most_recent_finished_event['waste_prevented'] = $most_recent_finished_event->WastePrevented;
        }

        $global_impact_data = app(\App\Http\Controllers\ApiController::class)
                            ->homepage_data();
        $global_impact_data = $global_impact_data->getData();

        $user_groups = [];

        if ($user) {
            foreach (UserGroups::where('user', $user->id)->pluck('group')->toArray() as $gid) {
                $user_groups[] = Group::find($gid);
            }
        }

        return view('fixometer.index', [
            'user' => $user,
            'user_groups' => $user_groups,
            'most_recent_finished_event' => $most_recent_finished_event,
            'impact_data' => $global_impact_data,
            'clusters' => $clusters,
            'barriers' => \App\Helpers\Fixometer::allBarriers(),
            'brands' => $brands,
            'item_types' => Device::getItemTypes(),
        ]);
    }

    public function ajaxCreate(Request $request)
    {
        $rules = [
            'category' => 'required|filled',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 200);
        }

        $category = $request->input('category');
        $weight = $request->filled('estimate') ? $request->input('estimate', 0) : 0;
        $brand = $request->input('brand');
        $model = $request->input('model');
        $item_type = $request->input('item_type');
        $age = $request->filled('age') ? $request->input('age',0) : 0;
        $problem = $request->input('problem');
        $notes = $request->input('notes');
        $repair_status = $request->input('repair_status');
        $repair_details = $request->input('repair_details');
        $spare_parts = $request->input('spare_parts');
        $quantity = $request->input('quantity');
        $event_id = $request->input('event_id');
        $barrier = $request->input('barrier');

        $iddevices = $request->input('iddevices');

        // Get party for later
        $event = Party::find($event_id);

        // add quantity loop
        for ($i = 0; $i < $quantity; $i++) {
            $device[$i] = new Device;
            $device[$i]->category = $category;
            $device[$i]->category_creation = $category;
            $device[$i]->estimate = $weight;
            $device[$i]->brand = $brand;
            $device[$i]->item_type = $item_type;
            $device[$i]->model = $model;
            $device[$i]->age = $age;
            $device[$i]->problem = $problem;
            $device[$i]->notes = $notes;
            $device[$i]->repair_status = isset($repair_status) ? $repair_status : 0;

            if ($repair_details == 1) {
                $device[$i]->more_time_needed = 1;
            } else {
                $device[$i]->more_time_needed = 0;
            }

            if ($repair_details == 2) {
                $device[$i]->professional_help = 1;
            } else {
                $device[$i]->professional_help = 0;
            }

            if ($repair_details == 3) {
                $device[$i]->do_it_yourself = 1;
            } else {
                $device[$i]->do_it_yourself = 0;
            }

            // $spare_parts as input comes from a single dropdown with three options.
            // This is mapped to corresponding DB values
            // for spare_parts and parts_provider columns.
            if ($spare_parts == 3) { // Input option was 'Third party'
                $spare_parts = Device::SPARE_PARTS_NEEDED;
                $parts_provider = Device::PARTS_PROVIDER_THIRD_PARTY;
            } elseif ($spare_parts == 1) { // Input option was 'Manufacturer'
                $spare_parts = Device::SPARE_PARTS_NEEDED;
                $parts_provider = Device::PARTS_PROVIDER_MANUFACTURER;
            } elseif ($spare_parts == 2) { // Input option was 'Not needed'
                $spare_parts = Device::SPARE_PARTS_NOT_NEEDED;
                $parts_provider = null;
            } else {
                $parts_provider = null;
            }

            if (! isset($barrier)) {
                $barrier = null;
            } elseif (in_array(1, $barrier) || in_array(2, $barrier)) { // 'Spare parts not available' or 'spare parts too expensive' selected
                $spare_parts = Device::SPARE_PARTS_NEEDED;
            } elseif (count($barrier) > 0) {
                $spare_parts = Device::SPARE_PARTS_NOT_NEEDED;
            }

            $device[$i]->spare_parts = isset($spare_parts) ? $spare_parts : Device::SPARE_PARTS_UNKNOWN;
            $device[$i]->parts_provider = $parts_provider;
            $device[$i]->event = $event_id;
            $device[$i]->repaired_by = Auth::id();

            $device[$i]->save();

            event(new DeviceCreatedOrUpdated($device[$i]));

            // Update barriers
            if (isset($barrier) && ! empty($barrier) && $repair_status == 3) { // Only sync when repair status is end-of-life
               Device::find($device[$i]->iddevices)->barriers()->sync($barrier);
            } else {
                Device::find($device[$i]->iddevices)->barriers()->sync([]);
            }

            // If the number of devices exceeds set amount then show the following message
            $deviceMiscCount = DB::table('devices')->where('category', env('MISC_CATEGORY_ID_POWERED'))->where('event', $event_id)->count() +
                DB::table('devices')->where('category', env('MISC_CATEGORY_ID_UNPOWERED'))->where('event', $event_id)->count();
            if ($deviceMiscCount == env('DEVICE_ABNORMAL_MISC_COUNT', 5)) {
                $notify_users = Fixometer::usersWhoHavePreference('admin-abnormal-devices');
                Notification::send($notify_users, new AdminAbnormalDevices([
                    'event_venue' => $event->getEventName(),
                    'event_url' => url('/party/edit/'.$event_id),
                ]));
            }

            // Expand a few things so that the new devices are returned with the same information that existing
            // ones are returned in the view blade.
            $device[$i]->idevents = $device[$i]->event;
            $device[$i]->category = $device[$i]->deviceCategory;
            $device[$i]->shortProblem = $device[$i]->getShortProblem();
            $device[$i]->urls;

            $barriers = [];

            foreach ($device[$i]->barriers as $barrier) {
                $barriers[] = $barrier->id;
            }

            $device[$i]->barrier = $barriers;

            if ($iddevices && $iddevices < 0) {
                // We might have some photos uploaded for this device.  Record them against this device instance.
                // Each instance of a device shares the same underlying photo file.
                $File = new \FixometerFile;
                $images = $File->findImages(env('TBL_DEVICES'), $iddevices);
                foreach ($images as $image) {
                    $xref = Xref::findOrFail($image->idxref);
                    $xref->copy($device[$i]->iddevices);
                }

                $device[$i]->images = $device[$i]->getImages();
            }
        }
        // end quantity loop

        $return['success'] = true;
        $return['devices'] = $device;

        $return['stats'] = $event->getEventStats();

        return response()->json($return);
    }

    public function ajaxEdit(Request $request, $id)
    {
        $category = $request->input('category');
        $brand = $request->input('brand');
        $item_type = $request->input('item_type');
        $model = $request->input('model');
        $age = $request->filled('age') ? $request->input('age',0) : 0;
        $problem = $request->input('problem');
        $notes = $request->input('notes');
        $repair_status = $request->input('repair_status');
        $barrier = $request->input('barrier');
        $repair_details = $request->input('repair_details');
        $spare_parts = $request->input('spare_parts');
        $event_id = $request->input('event_id');
        $wiki = $request->input('wiki');
        $estimate = $request->filled('estimate') ? $request->input('estimate', 0) : 0;

        if (empty($repair_status)) { //Override
            $repair_status = 0;
        }

        if ($repair_status != 2) { //Override
            $repair_details = 0;
        }

        if (Fixometer::userHasEditEventsDevicesPermission($event_id)) {
            if ($repair_details == 1) {
                $more_time_needed = 1;
            } else {
                $more_time_needed = 0;
            }

            if ($repair_details == 2) {
                $professional_help = 1;
            } else {
                $professional_help = 0;
            }

            if ($repair_details == 3) {
                $do_it_yourself = 1;
            } else {
                $do_it_yourself = 0;
            }

            $old_wiki = Device::find($id)->wiki;

            if ($spare_parts == 3) { // Third party
                $spare_parts = 1;
                $parts_provider = 2;
            } elseif ($spare_parts == 1) { // Manufacturer
                $spare_parts = 1;
                $parts_provider = 1;
            } elseif ($spare_parts == 2) { // Not needed
                $spare_parts = 2;
                $parts_provider = null;
            } elseif ($spare_parts == 4) { // Historical data, resets spare parts to 1 but keeps parts provider as null
                $spare_parts = 1;
                $parts_provider = null;
            } else {
                $parts_provider = null;
            }

            if (! isset($barrier)) {
                $barrier = null;
            } elseif (in_array(1, $barrier) || in_array(2, $barrier)) { // 'Spare parts not available' or 'spare parts too expensive' selected
                $spare_parts = 1;
            } elseif (count($barrier) > 0) {
                $spare_parts = 2;
            }

            $Device = Device::find($id);

            $Device->update([
                'category' => $category,
                'category_creation' => $category,
                'brand' => $brand,
                'item_type' => $item_type,
                'model' => $model,
                'age' => $age,
                'problem' => $problem,
                'notes' => $notes,
                'spare_parts' => $spare_parts,
                'parts_provider' => $parts_provider,
                'repair_status' => $repair_status,
                'more_time_needed' => $more_time_needed,
                'do_it_yourself' => $professional_help,
                'professional_help' => $do_it_yourself,
                'wiki' => $wiki,
                'estimate' => $estimate,
            ]);

            // Update barriers
            if (isset($barrier) && ! empty($barrier) && $repair_status == 3) { // Only sync when repair status is end-of-life
                  $Device->barriers()->sync($barrier);
            } else {
                $Device->barriers()->sync([]);
            }

            $event = Party::find($event_id);
            event(new DeviceCreatedOrUpdated($Device));

            $stats = $event->getEventStats();
            $data['stats'] = $stats;
            $data['success'] = 'Device updated!';

            // Expand a few things so that the devices are returned with the same information that existing
            // ones are returned in the view blade.
            $device = Device::find($id);
            $device->idevents = $device->event;
            $device->category = $device->deviceCategory;
            $device->shortProblem = $device->getShortProblem();
            $device->images = $device->getImages();
            $device->urls;

            $barriers = [];

            foreach ($device->barriers as $barrier) {
                $barriers[] = $barrier->id;
            }

            $device->barrier = $barriers;

            $data['device'] = $device;

            return response()->json($data);
        }
    }

    public function delete(Request $request, $id)
    {
        $user = Auth::user();

        $device = Device::find($id);

        if ($device) {
            $eventId = $device->event;
            $is_attending = EventsUsers::where('event', $device->event)->where('user', Auth::id())->first();
            $is_attending = is_object($is_attending) && $is_attending->status == 1;

            if (Fixometer::hasRole($user, 'Administrator') ||
                Fixometer::userHasEditPartyPermission($eventId, $user->id) ||
                $is_attending
            ) {
                $device->delete();

                if ($request->ajax()) {
                    $event = Party::find($eventId);
                    $stats = $event->getEventStats();

                    return response()->json([
                                                'success' => true,
                                                'stats' => $stats,
                                            ]);
                }

                return redirect('/party/view/'.$eventId)->with('success', 'Device has been deleted!');
            }
        }

        if ($request->ajax()) {
            return response()->json(['success' => false]);
        }

        \Sentry\CaptureMessage('You do not have the right permissions for deleting a device');
        return redirect('/party/view/'.$eventId)->with('warning', 'You do not have the right permissions for deleting a device');
    }

    public function imageUpload(Request $request, $id)
    {
        try {
            $images = [];

            if (isset($_FILES) && ! empty($_FILES)) {
                $file = new FixometerFile;

                if ($id > 0) {
                    // We are adding a photo to an existing device.
                    $fn = $file->upload('file', 'image', $id, env('TBL_DEVICES'), true, false, true);
                    $device = Device::find($id);
                    $images = $device->getImages();
                } else {
                    // We are adding a photo for a device that hasn't yet been added.  Upload the file. We will add
                    // them to the device once the device is created.
                    $fn = $file->upload('file', 'image', $id, env('TBL_DEVICES'), true, false, true);

                    if ($fn) {
                        $File = new \FixometerFile;
                        $images = $File->findImages(env('TBL_DEVICES'), $id);
                    } else {
                        return 'fail - image could not be uploaded';
                    }
                }
            }

            // Return the current set of images for this device so that the client doesn't need to merge.
            return response()->json([
                'success' => true,
                'iddevices' => $id,
                'images' => $images,
            ]);
        } catch (\Exception $e) {
            error_log("Exception  " . $e->getMessage());
            return 'fail - image could not be uploaded';
        }
    }

    public function deleteImage($device_id, $idxref)
    {
        $user = Auth::user();

        if ($device_id > 0) {
            // We are deleting a photo from an existing device.
            $event_id = Device::find($device_id)->event;
            $in_event = EventsUsers::where('event', $event_id)->where('user', Auth::user()->id)->first();
            if (Fixometer::hasRole($user, 'Administrator') || is_object($in_event)) {
                $Image = new FixometerFile;
                $Image->deleteImage($idxref);

                return redirect()->back()->with('message', 'Thank you, the image has been deleted');
            }

            return redirect()->back()->with('message', 'Sorry, but the image can\'t be deleted');
        } else {
            // We are deleting a photo from a device which has not yet been added.
            //
            // There is a slight security issue here, in that one user could delete the photos from devices which
            // are in the process of being added by another user.  The chances of this being a real issue are very low.
            $Image = new FixometerFile;
            $Image->deleteImage($idxref);

            return redirect()->back()->with('message', 'Thank you, the image has been deleted');
        }
    }
}
