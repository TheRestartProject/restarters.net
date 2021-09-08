<?php

namespace App\Http\Controllers;

use App\Brands;
use App\Category;
use App\Cluster;
use App\Device;
use App\DeviceList;
use App\DeviceUrl;
use App\Events\DeviceCreatedOrUpdated;
use App\EventsUsers;
use App\Group;
use App\Helpers\Fixometer;
use App\Helpers\FootprintRatioCalculator;
use App\Notifications\AdminAbnormalDevices;
use App\Notifications\ReviewNotes;
use App\Party;
use App\User;
use App\UserGroups;
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
        ->orderBy('event_date', 'DESC')
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

    public function edit($id)
    {
        // This page now only exists to provide a kind of API for updating a device using POST.
        $device = Device::find($id);

        $is_attending = EventsUsers::where('event', $device->event)->where('user', Auth::id())->first();

        $user = Auth::user();

        if (Fixometer::hasRole($user, 'Administrator') || ! empty($is_attending)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && ! empty($_POST) && filter_var($id, FILTER_VALIDATE_INT)) {
                $data = $_POST;

                // Remove some inputs.  Probably these aren't present any more, but it does no harm to ensure that.
                unset($data['files']);
                unset($data['users']);

                $old_wiki = Device::find($id)->wiki;

                if (isset($data['wiki'])) {
                    $wiki = 1;
                } else {
                    $wiki = 0;
                }

                //Send Wiki Notification to Admins
                if (env('APP_ENV') != 'development' && env('APP_ENV') != 'local' && ($wiki == 1 && $old_wiki !== 1)) {
                    $all_admins = User::where('role', 2)->get();
                    $group_id = Party::find($data['event'])->group;

                    $arr = [
                        'group_url' => url('/group/view/'.$group_id),
                        'preferences' => url('/profile/edit'),
                    ];

                    Notification::send($all_admins, new ReviewNotes($arr));
                }

                if (! isset($data['repair_more']) || empty($data['repair_more'])) { //Override
                    $data['repair_more'] = 0;
                }

                if ($data['repair_status'] != 2) { //Override
                    $data['repair_more'] = 0;
                }

                if ($data['repair_more'] == 1) {
                    $more_time_needed = 1;
                } else {
                    $more_time_needed = 0;
                }

                if ($data['repair_more'] == 2) {
                    $professional_help = 1;
                } else {
                    $professional_help = 0;
                }

                if ($data['repair_more'] == 3) {
                    $do_it_yourself = 1;
                } else {
                    $do_it_yourself = 0;
                }

                if ($data['category'] == 46 && isset($data['weight'])) {
                    $weight = $data['weight'];
                } else {
                    $weight = null;
                }

                if ($data['spare_parts'] == 3) { // Third party
                    $data['spare_parts'] = 1;
                    $parts_provider = 2;
                } elseif ($data['spare_parts'] == 1) { // Manufacturer
                    $data['spare_parts'] = 1;
                    $parts_provider = 1;
                } elseif ($data['spare_parts'] == 2) { // Not needed
                    $data['spare_parts'] = 2;
                    $parts_provider = null;
                } elseif ($data['spare_parts'] == 4) { // Historical data, resets spare parts to 1 but keeps parts provider as null
                    $data['spare_parts'] = 1;
                    $parts_provider = null;
                } else {
                    $parts_provider = null;
                }

                if (! isset($data['barrier'])) {
                    $data['barrier'] = null;
                } elseif (in_array(1, $data['barrier']) || in_array(2, $data['barrier'])) { // 'Spare parts not available' or 'spare parts too expensive' selected
                    $data['spare_parts'] = 1;
                } elseif (count($data['barrier']) > 0) {
                    $data['spare_parts'] = 2;
                }

                $update = [
                    'event' => $data['event'],
                    'category' => $data['category'],
                    'category_creation' => $data['category'],
                    'estimate' => $weight,
                    'repair_status' => $data['repair_status'],
                    'spare_parts' => $data['spare_parts'],
                    'parts_provider' => $parts_provider,
                    'brand' => $data['brand'],
                    'model' => $data['model'],
                    'problem' => $data['problem'],
                    'age' => $data['age'],
                    'more_time_needed' => $more_time_needed,
                    'professional_help' => $professional_help,
                    'do_it_yourself' => $do_it_yourself,
                    'wiki' => $wiki,
                ];

                $u = Device::find($id)->update($update);

                // Update barriers
                if (isset($data['barrier']) && ! empty($data['barrier']) && $data['repair_status'] == 3) { // Only sync when repair status is end-of-life
                      Device::find($id)->barriers()->sync($data['barrier']);
                } else {
                    Device::find($id)->barriers()->sync([]);
                }

                if (! $u) {
                    $response['danger'] = 'Something went wrong. Please check the data and try again.';
                } else {
                    $response['success'] = 'Device updated!';

                    /* let's create the image attachment! **/
                    if (isset($_FILES) && ! empty($_FILES['files']['name'])) {
                        $file = new FixometerFile;
                        $file->upload('devicePhoto', 'image', $id, env('TBL_DEVICES'), true);
                    }
                }

                event(new DeviceCreatedOrUpdated(Device::find($id)));
            }
        }

        return redirect('/user/forbidden');
    }

    public function ajax_update($id)
    {
        $this->set('title', 'Edit Device');
        if (hasRole($this->user, 'Administrator') || hasRole($this->user, 'Host')) {
            $Categories = new Category;
            $Device = $this->Device->findOne($id);

            $this->set('title', 'Edit Device');
            $this->set('categories', $Categories->listed());
            $this->set('formdata', $Device);

            event(new DeviceCreatedOrUpdated($Device));

            return view('device.edit', [
                'title' => 'Edit Device',
                'categories' => $Categories->findAll(),
                'formdata' => $Device,
            ]);
        }
        header('Location: /user/forbidden');
    }

    public function ajax_update_save($id)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && ! empty($_POST) && filter_var($id, FILTER_VALIDATE_INT)) {
            $data = $_POST;
            $u = $this->Device->update($data, $id);

            if (! $u) {
                $response['response_type'] = 'danger';
                $response['message'] = 'Something went wrong. Please check the data and try again.';
            } else {
                $response['response_type'] = 'success';
                $response['message'] = 'Device updated!';
                $response['data'] = $data;
                $response['id'] = $id;
            }

            event(new DeviceCreatedOrUpdated($this->Device));

            echo json_encode($response);
        }
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
        $weight = $request->input('estimate');
        $brand = $request->input('brand');
        $model = $request->input('model');
        $item_type = $request->input('item_type');
        $age = $request->input('age');
        $problem = $request->input('problem');
        $notes = $request->input('notes');
        $repair_status = $request->input('repair_status');
        $repair_details = $request->input('repair_details');
        $spare_parts = $request->input('spare_parts');
        $quantity = $request->input('quantity');
        $event_id = $request->input('event_id');
        $barrier = $request->input('barrier');

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
            $deviceMiscCount = DB::table('devices')->where('category', 46)->where('event', $event_id)->count();
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
        $weight = $request->input('weight');
        $brand = $request->input('brand');
        $item_type = $request->input('item_type');
        $model = $request->input('model');
        $age = $request->input('age');
        $problem = $request->input('problem');
        $notes = $request->input('notes');
        $repair_status = $request->input('repair_status');
        $barrier = $request->input('barrier');
        $repair_details = $request->input('repair_details');
        $spare_parts = $request->input('spare_parts');
        $event_id = $request->input('event_id');
        $wiki = $request->input('wiki');
        $estimate = $request->input('estimate');

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

            //Send Wiki Notification to Admins
            try {
                if ($wiki == 1 && $old_wiki !== 1) {
                    $currentUser = Auth::user();

                    $all_admins = User::where('role', 2)->get();
                    $group_id = Party::find($event_id)->group;

                    $arr = [
                        'device_url' => url('/device/page-edit/'.$id),
                        'current_user_name' => $currentUser->name,
                        'group_url' => url('/group/view/'.$group_id),
                        'preferences' => url('/profile/edit'),
                    ];

                    Notification::send($all_admins, new ReviewNotes($arr));
                }
            } catch (\Exception $ex) {
                Log::error('An error occurred while sending ReviewNotes email: '.$ex->getMessage());
            }

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
                'estimate' => $weight,
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
        $eventId = $device->event;

        if (Fixometer::hasRole($user, 'Administrator') || Fixometer::userHasEditPartyPermission($eventId, $user->id)) {
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

        if ($request->ajax()) {
            return response()->json(['success' => false]);
        }

        return redirect('/party/view/'.$eventId)->with('warning', 'You do not have the right permissions for deleting a device');
    }

    public function imageUpload(Request $request, $id)
    {
        try {
            $images = [];

            if (isset($_FILES) && ! empty($_FILES)) {
                $file = new FixometerFile;
                $file->upload('file', 'image', $id, env('TBL_DEVICES'), true, false, true);
                $device = Device::find($id);
                $images = $device->getImages();
            }

            // Return the current set of images for this device so that the client doesn't need to merge.
            return response()->json([
                'success' => true,
                'iddevices' => $id,
                'images' => $images,
            ]);
        } catch (\Exception $e) {
            return 'fail - image could not be uploaded';
        }
    }

    public function deleteImage($device_id, $id, $path)
    {
        $user = Auth::user();

        $event_id = Device::find($device_id)->event;
        $in_event = EventsUsers::where('event', $event_id)->where('user', Auth::user()->id)->first();
        if (Fixometer::hasRole($user, 'Administrator') || is_object($in_event)) {
            $Image = new FixometerFile;
            $Image->deleteImage($id, basename($path));

            return redirect()->back()->with('message', 'Thank you, the image has been deleted');
        }

        return redirect()->back()->with('message', 'Sorry, but the image can\'t be deleted');
    }

    public function columnPreferences(Request $request)
    {
        $request->session()->put('column_preferences', $request->input('column_preferences'));
    }
}
