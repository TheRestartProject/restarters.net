<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use App\Models\Brands;
use App\Models\Cluster;
use App\Models\Device;
use App\Events\DeviceCreatedOrUpdated;
use App\Models\EventsUsers;
use App\Models\Group;
use App\Helpers\Fixometer;
use App\Notifications\AdminAbnormalDevices;
use App\Models\Party;
use App\Models\User;
use App\Models\UserGroups;
use App\Models\Xref;
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
    public function index($search = null): \Illuminate\View\View
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
        ]);
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
                    $device = Device::findOrFail($id);
                    $images = $device->getImages();
                } else {
                    // We are adding a photo for a device that hasn't yet been added.  Upload the file. We will add
                    // them to the device once the device is created.
                    $fn = $file->upload('file', 'image', $id, env('TBL_DEVICES'), true, false, true);

                    if ($fn) {
                        $File = new \FixometerFile;
                        $images = $File->findImages(env('TBL_DEVICES'), $id);
                    } else {
                        return __('devices.image_upload_error');
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
            \Sentry\CaptureMessage("Image upload exception  " . $e->getMessage());
            return __('devices.image_upload_error');
        }
    }

    public function deleteImage($device_id, $idxref): RedirectResponse
    {
        $user = Auth::user();

        if ($device_id > 0) {
            // We are deleting a photo from an existing device.
            $event_id = Device::find($device_id)->event;
            $in_event = EventsUsers::where('event', $event_id)->where('user', Auth::user()->id)->first();
            if (Fixometer::hasRole($user, 'Administrator') || is_object($in_event)) {
                $Image = new FixometerFile;
                $Image->deleteImage($idxref);

                return redirect()->back()->with('message', __('devices.image_delete_success'));
            }

            return redirect()->back()->with('message', __('devices.image_delete_error'));
        } else {
            // We are deleting a photo from a device which has not yet been added.
            //
            // There is a slight security issue here, in that one user could delete the photos from devices which
            // are in the process of being added by another user.  The chances of this being a real issue are very low.
            $Image = new FixometerFile;
            $Image->deleteImage($idxref);

            return redirect()->back()->with('message', __('devices.image_delete_success'));
        }
    }
}
