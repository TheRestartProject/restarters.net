<?php

namespace App\Http\Controllers;

use App\Device;
use App\DeviceUrl;
use App\Helpers\Fixometer;
use Auth;
use Illuminate\Http\Request;

class DeviceUrlController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        // Get device
        $device = Device::find($request->input('device_id'));

        // Check we have the permission
        if ($device && Fixometer::hasRole(Auth::user(), 'Administrator') || Fixometer::userHasEditPartyPermission($device->event, Auth::user()->id)) {
            // Create URL
            $create = DeviceUrl::create([
            'device_id' => $request->input('device_id'),
            'source' => $request->input('source'),
            'url' => $request->input('url'),
            ]);

            // Return information
            if ($create) {
                return response()->json([
                    'success' => true,
                    'id' => $create->id,
                ]);
            }
        }

        // All else fails
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\DeviceUrl  $deviceUrl
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DeviceUrl $deviceUrl)
    {
        // Check we have the permission
        if (Fixometer::hasRole(Auth::user(), 'Administrator') || Fixometer::userHasEditPartyPermission($deviceUrl->device->event, Auth::user()->id)) {
            // Create URL
            $update = DeviceUrl::find($deviceUrl->id)->update([
              'url' => $request->input('url'),
              'source' => $request->input('source'),
            ]);

            // Return information
            if ($update) {
                return response()->json([
                    'success' => true,
                ]);
            }
        }

        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\DeviceUrl  $deviceUrl
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeviceUrl $deviceUrl)
    {

        // Check we have the permission
        if (Fixometer::hasRole(Auth::user(), 'Administrator') || Fixometer::userHasEditPartyPermission($deviceUrl->device->event, Auth::user()->id)) {
            // Delete URL
            $device = DeviceUrl::where('id', $deviceUrl->id)->delete();
            if ($device) {
                return response()->json([
                    'success' => true,
                ]);
            }
        }

        abort(404);
    }
}
