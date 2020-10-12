<?php

namespace App\Http\Controllers;

use App\Brands;

use App\Category;
use App\Cluster;
use App\Device;
use App\DeviceList;
use App\DeviceUrl;
use App\EventsUsers;
use App\Group;
use App\Helpers\FootprintRatioCalculator;
use App\Notifications\AdminAbnormalDevices;
use App\Notifications\ReviewNotes;
use App\Party;
use App\User;
use App\UserGroups;
use Auth;
use FixometerFile;
use FixometerHelper;
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
        $Category = new Category;

        $categories = $Category->listed();

        $all_groups = Group::all();

        $most_recent_finished_event = Party::with('theGroup')
        ->hasDevicesRepaired(1)
        ->eventHasFinished()
        ->orderBy('event_date', 'DESC')
        ->first();

        $global_impact_data = app('App\Http\Controllers\ApiController')
                            ->homepage_data();
        $global_impact_data = $global_impact_data->getData();

        $user_groups = Group::with('allRestarters', 'parties', 'groupImage.image')
        ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
        ->join('events', 'events.group', '=', 'groups.idgroups')
        ->where('users_groups.user', Auth::id())
        ->orderBy('groups.name', 'ASC')
        ->groupBy('groups.idgroups')
        ->select('groups.*')
        ->get();

        $items = Device::with(['deviceEvent'])
               ->join('events', 'events.idevents', '=', 'devices.event')
               ->orderBy('events.event_date', 'desc')
               ->orderBy('devices.iddevices', 'asc')
               ->paginate(15);

        return view('fixometer.index', [
            'title' => Lang::get('devices.fixometer'),
            'categories' => $categories,
            'groups' => $all_groups,
            'most_recent_finished_event' => $most_recent_finished_event,
            'impact_data' => $global_impact_data,
            'selected_groups' => null,
            'selected_categories' => null,
            'from_date' => null,
            'to_date' => null,
            'device_id' => null,
            'brand' => null,
            'model' => null,
            'problem' => null,
            'wiki' => null,
            'status' => null,
            'sort_direction' => 'DSC',
            'sort_column' => 'event_date',
            'user_groups' => $user_groups,
            'items' => $items,
        ]);
    }

    public function search(Request $request, $raw = false)
    {
        $Category = new Category;
        $categories = $Category->listed();

        $sort_direction = $request->input('sort_direction');
        $sort_column = $request->input('sort_column');

        $all_devices = Device::with([
          'deviceCategory',
          'deviceEvent',
          'barriers'
        ])
        ->join('events', 'events.idevents', '=', 'devices.event')
        ->join('groups', 'groups.idgroups', '=', 'events.group')
        ->select('devices.*', 'groups.name AS group_name');

        if ($request->input('sort_column') !== null) {
            $all_devices = $all_devices->orderBy($sort_column, $sort_direction);
        }

        if ($request->input('categories') !== null) {
            $all_devices = $all_devices->whereIn('devices.category', $request->input('categories'));
        }

        if ($request->input('groups') !== null) {
            $all_devices = $all_devices->whereIn('groups.idgroups', $request->input('groups'));
        }

        if ($request->input('wiki')) {
            $all_devices = $all_devices->where('devices.wiki', true);
        }

        $date_from = $request->get('from-date');
        $date_to = $request->get('to-date');

        if (! empty($date_from)) {
            $d_from = \DateTime::createFromFormat('Y-m-d', $date_from);
            $from = $d_from->format('Y-m-d').' 00:00:00';
        }

        if (! empty($date_to)) {
            $d_to = \DateTime::createFromFormat('Y-m-d', $date_to);
            $to = $d_to->format('Y-m-d').' 23:59:59';
        }

        if (empty($date_from) && empty($date_to)) {
          $all_devices->whereHas('deviceEvent', function($query) {
            return $query->whereDate('event_date', '<', date('Y-m-d'));
          });
        } elseif (! empty($date_from) && ! empty($date_to)) {
            $all_devices = $all_devices->whereBetween('event_date', [$from, $to]);
        } elseif (! empty($date_from)) {
            $all_devices = $all_devices->whereDate('event_date', '>=', $from);
        } elseif (! empty($date_to)) {
            $to = $d_to->format('Y-m-d').' 23:59:59';
            $all_devices = $all_devices->whereDate('event_date', '<=', $to);
        }

        if ($request->input('device_id') !== null) {
            $all_devices = $all_devices->where('id', 'like', $request->input('device_id').'%');
        }

        if ($request->input('status') !== null) {
            $all_devices = $all_devices->whereIn('repair_status', $request->input('status'));
        }

        if ($request->input('brand') !== null) {
            $all_devices = $all_devices->where('brand', 'like', '%'.$request->input('brand').'%');
        }

        if ($request->input('model') !== null) {
            $all_devices = $all_devices->where('model', 'like', '%'.$request->input('model').'%');
        }

        if ($request->input('problem') !== null) {
            $all_devices = $all_devices->where('problem', 'like', '%'.$request->input('problem').'%');
        }

        if ($raw == true) {
            return $all_devices->get();
        }

        $all_deviced_grouped = $all_devices->get()
        ->groupBy('event');

        $all_devices_paginated = $all_devices->paginate(env('PAGINATE'));

        $footprintRatioCalculator = new FootprintRatioCalculator();
        $emissionRatio = $footprintRatioCalculator->calculateRatio();

        $global_impact_data = app('App\Http\Controllers\ApiController')
                            ->homepage_data();
        $global_impact_data = $global_impact_data->getData();

        $most_recent_finished_event = Party::with('theGroup')
        ->hasDevicesRepaired(1)
        ->eventHasFinished()
        ->orderBy('event_date', 'DESC')
        ->first();

        $user_groups = Group::with('allRestarters', 'parties', 'groupImage.image')
        ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
        ->join('events', 'events.group', '=', 'groups.idgroups')
        ->where('users_groups.user', Auth::id())
        ->orderBy('groups.name', 'ASC')
        ->groupBy('groups.idgroups')
        ->select('groups.*')
        ->get();

        return view('fixometer.index', [
            'impact_data' => $global_impact_data,
            'title' => 'Devices',
            'categories' => $categories,
            'groups' => Group::all(),
            'most_recent_finished_event' => $most_recent_finished_event,
            'items' => $all_devices_paginated,
            'selected_groups' => $request->input('groups'),
            'selected_categories' => $request->input('categories'),
            'from_date' => $request->input('from-date'),
            'to_date' => $request->input('to-date'),
            'device_id' => $request->input('device_id'),
            'brand' => $request->input('brand'),
            'model' => $request->input('model'),
            'problem' => $request->input('problem'),
            'status' => $request->input('status'),
            'wiki' => $request->input('wiki'),
            'sort_direction' => $sort_direction,
            'sort_column' => $sort_column,
            'user_groups' => $user_groups,
        ]);
    }

    public function edit($id)
    {
        // $this->set('title', 'Edit Device');

        $device = Device::find($id);

        $is_attending = EventsUsers::where('event', $device->event)->where('user', Auth::id())->first();

        $user = Auth::user();
        if (FixometerHelper::hasRole($user, 'Administrator') || ! empty($is_attending)) {
            $is_host = FixometerHelper::userHasEditPartyPermission($device->event, $user->id);

            $Device = new Device;

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && ! empty($_POST) && filter_var($id, FILTER_VALIDATE_INT)) {
                $data = $_POST;
                // remove the extra "files" field that Summernote generates -
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

                // formatting dates for the DB
                //$data['event_date'] = dbDateNoTime($data['event_date']);

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

                // New logic Nov 2018
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
                // EO new logic Nov 2018

                $update = array(
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
                );

                // $u = $Device->where('iddevices', $id)->update($update);
                $u = Device::find($id)->update($update);

                // Update barriers
                if (isset($data['barrier']) && ! empty($data['barrier']) && $data['repair_status'] == 3) { // Only sync when repair status is end-of-life
                      $device = Device::find($id)->barriers()->sync($data['barrier']);
                } else {
                    $device = Device::find($id)->barriers()->sync([]);
                }

                if (! $u) {
                    $response['danger'] = 'Something went wrong. Please check the data and try again.';
                } else {
                    $response['success'] = 'Device updated!';

                    /** let's create the image attachment! **/
                    if (isset($_FILES) && ! empty($_FILES['files']['name'])) {
                        $file = new FixometerFile;
                        $file->upload('devicePhoto', 'image', $id, env('TBL_DEVICES'), true);
                    }
                }
            }
            $Events = new Party;
            $Categories = new Category;
            $File = new FixometerFile;

            $UserEvents = $Events->findAll();

            $device = $Device->findOne($id);

            if (! isset($response)) {
                $response = null;
            }

            $images = $File->findImages(env('TBL_DEVICES'), $id);

            if (! isset($images)) {
                $images = null;
            }

            $brands = Brands::all();

            $audits = Device::findOrFail($id)->audits;

            return view('fixometer.edit', [
                'title' => 'Edit Device',
                'response' => $response,
                'categories' => $Categories->findAll(),
                'events' => $UserEvents,
                'formdata' => $device,
                'brands' => $brands,
                'user' => $user,
                'is_host' => $is_host,
                'images' => $images,
                'audits' => $audits,
            ]);
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

            echo json_encode($response);
        }
    }

    public function create()
    {
        $user = Auth::user();

        if (FixometerHelper::hasRole($user, 'Restarter')) {
            header('Location: /user/forbidden');
        } else {
            $Events = new Party;
            $Categories = new Category;

            $UserEvents = $Events->ofThisUser($user->id);

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && ! empty($_POST)) {
                $error = array();
                $data = array_filter($_POST);
                $Device = new Device;

                if (! FixometerHelper::verify($data['event'])) {
                    $error['event'] = 'Please select a Restart party.';
                }
                if (! FixometerHelper::verify($data['category'])) {
                    $error['category'] = 'Please select a category for this device';
                }
                if (! FixometerHelper::verify($data['repair_status'])) {
                    $error['repair_status'] = 'Please select a repair status.';
                }

                if (! empty($error)) {
                    $response['danger'] = 'The device repair has <strong>not</strong> been saved.';
                } else {
                    // add user id
                    $data['repaired_by'] = $user->id;
                    // add initial category (for backlogging upon revision)
                    $data['category_creation'] = $data['category'];

                    $insert = array(
                        'event' => $data['event'],
                        'category' => $data['category'],
                        'category_creation' => $data['category'],
                        'repair_status' => $data['repair_status'],
                        'spare_parts' => $data['spare_parts'],
                        'brand' => $data['brand'],
                        'model' => $data['model'],
                        'problem' => $data['problem'],
                        'repaired_by' => $data['repaired_by'],
                    );

                    // save this!
                    $insert = $Device->create($insert);
                    if (! $insert) {
                        $response['danger'] = 'Error while saving the device to the DB.';
                    } else {
                        $response['success'] = 'Device saved!';
                    }
                }
            }

            if (! isset($error)) {
                $error = null;
            }

            if (! isset($response)) {
                $response = null;
            }

            if (! isset($data)) {
                $data = null;
            }

            return view('device.create', [
                'title' => 'New Device',
                'categories' => $Categories->findAll(),
                'events' => $UserEvents,
                'response' => $response,
                'udata' => $data,
                'error' => $error,
            ]);
        }
    }

    public function ajaxCreate(Request $request)
    {
        $powered = NULL;

        $rules = [
            'category' => 'required|filled',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 200);
        }

        $category = $request->input('category');
        $weight = $request->input('weight');
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
        $useful_url = $request->input('url');
        $useful_source = $request->input('source');

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

            // New logic Nov 2018
            if ($spare_parts == 3) { // Third party
                $spare_parts = 1;
                $parts_provider = 2;
            } elseif ($spare_parts == 1) { // Manufacturer
                $spare_parts = 1;
                $parts_provider = 1;
            } elseif ($spare_parts == 2) { // Not needed
                $spare_parts = 2;
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

            $device[$i]->spare_parts = isset($spare_parts) ? $spare_parts : 0;
            $device[$i]->parts_provider = $parts_provider;
            $device[$i]->event = $event_id;
            $device[$i]->repaired_by = Auth::id();

            $device[$i]->save();

            if ($useful_url) {
                // Devices can have multiple URLs, but we only support one on the create - and it gets applied to each
                // device.
                DeviceUrl::create([
                  'device_id' => $device[$i]->iddevices,
                  'source' => $useful_source,
                  'url' => $useful_url
               ]);
            }

            // Update barriers
            if (isset($barrier) && ! empty($barrier) && $repair_status == 3) { // Only sync when repair status is end-of-life
                Device::find($device[$i]->iddevices)->barriers()->sync($barrier);
            } else {
                Device::find($device[$i]->iddevices)->barriers()->sync([]);
            }

            // If the number of devices exceeds set amount then show the following message
            $deviceMiscCount = DB::table('devices')->where('category', 46)->where('event', $event_id)->count();
            if ($deviceMiscCount == env('DEVICE_ABNORMAL_MISC_COUNT', 5)) {
                $notify_users = FixometerHelper::usersWhoHavePreference('admin-abnormal-devices');
                Notification::send($notify_users, new AdminAbnormalDevices([
                    'event_venue' => $event->getEventName(),
                    'event_url' => url('/party/edit/'.$event_id),
                ]));
            }

            // Expand a few things so that the new devices are returned with the same information that existing
            // ones are returned in view.
            $device[$i]->idevents = $device[$i]->event;
            $device[$i]->category = $device[$i]->deviceCategory;
            $device[$i]->shortProblem = $device[$i]->getShortProblem();
            $device[$i]->urls;
        }
        // end quantity loop

        $return['success'] = true;
        $return['devices'] = $device;

        $footprintRatioCalculator = new FootprintRatioCalculator();
        $emissionRatio = $footprintRatioCalculator->calculateRatio();
        $return['stats'] = $event->getEventStats($emissionRatio);

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

        if (FixometerHelper::userHasEditEventsDevicesPermission($event_id)) {
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

            Device::find($id)->update([
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
                'estimate' => $estimate
            ]);

            // Update barriers
            if (isset($barrier) && ! empty($barrier) && $repair_status == 3) { // Only sync when repair status is end-of-life
                  $device = Device::find($id)->barriers()->sync($barrier);
            } else {
                $device = Device::find($id)->barriers()->sync([]);
            }

            $event = Party::find($event_id);

            $footprintRatioCalculator = new FootprintRatioCalculator();
            $emissionRatio = $footprintRatioCalculator->calculateRatio();

            $stats = $event->getEventStats($emissionRatio);
            $data['stats'] = $stats;

            // if ($repair_status == 0) {
            //   $data['error'] = "Device couldn't be updated - no repair details added";
            //   return response()->json($data);
            // }

            $data['success'] = 'Device updated!';

            // Expand a few things so that the devices are returned with the same information that existing
            // ones are returned in view.
            $device = Device::find($id);
            $device->idevents = $device->event;
            $device->category = $device->deviceCategory;
            $device->shortProblem = $device->getShortProblem();
            $device->urls;

            $data['device'] = $device;

            return response()->json($data);

            // } else {
          //
          //   Device::find($id)->update([
          //     'category' => $category,
          //     'category_creation' => $category,
          //     'brand' => $brand,
          //     'model' => $model,
          //     'age' => $age,
          //     'problem' => $problem,
          //     'spare_parts' => $spare_parts,
          //     'repair_status' => $repair_status,
          //     'more_time_needed' => 0,
          //     'professional_help' => 0,
          //     'do_it_yourself' => 0,
          //     'wiki' => $wiki,
          //   ]);
          //
          //   $event = Party::find($event_id);
          //
          //   $Device = new Device;
          //   $weights = $Device->getWeights();
          //
          //   $TotalWeight = $weights[0]->total_weights;
          //   $TotalEmission = $weights[0]->total_footprints;
          //   $EmissionRatio = $TotalEmission / $TotalWeight;
          //   $stats = $event->getEventStats($EmissionRatio);
          //   $data['stats'] = $stats;
          //
          //   $data['success'] = "Device updated!";
          //
          //   return response()->json($data);
          //
          // }
        }
    }

    public function delete(Request $request, $id)
    {
        $user = Auth::user();

        $device = Device::find($id);
        $eventId = $device->event;

        if (FixometerHelper::hasRole($user, 'Administrator') || FixometerHelper::userHasEditPartyPermission($eventId, $user->id)) {
            $device->delete();

            if ($request->ajax()) {
                $footprintRatioCalculator = new FootprintRatioCalculator();
                $emissionRatio = $footprintRatioCalculator->calculateRatio();
                $event = Party::find($eventId);
                $stats = $event->getEventStats($emissionRatio);

                return response()->json([
                    'success' => true,
                    'stats' => $stats
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


            // Return the current set of images.
            return response()->json([
                'success' => true,
                'iddevices' => $id,
                'images' => $images
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
        if (FixometerHelper::hasRole($user, 'Administrator') || is_object($in_event)) {
            $Image = new FixometerFile;
            $Image->deleteImage($id, $path);

            return redirect()->back()->with('message', 'Thank you, the image has been deleted');
        }

        return redirect()->back()->with('message', 'Sorry, but the image can\'t be deleted');
    }

    public function columnPreferences(Request $request)
    {
        $request->session()->put('column_preferences', $request->input('column_preferences'));
    }

    // public function test() {
  //   $g = new Device;
  //   dd($g->export());
  // }
}
