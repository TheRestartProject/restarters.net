<?php

namespace App\Http\Controllers;

use App\Faultcat;
use App\MicrotaskDemographics;
use Auth;
use Illuminate\Http\Request;
use Session;

class FaultcatController extends Controller
{
    /**
     * Fetch / post random fault.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return redirect()->action([\App\Http\Controllers\FaultcatController::class, 'status'])->withSuccess('done');
    }

    public function demographics(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = new \stdClass();
            $user->id = 0;
            $user->name = 'Guest';
        }

        return view('faultcat.demographics', [
            'user' => $user,
        ]);
    }

    /**
     * Store demographic information from anonymous users.
     */
    public function storeDemographics(Request $request)
    {
        $validatedData = $request->validate([
            'age' => 'required',
            'country' => 'required',
        ]);

        $age = $validatedData['age'];
        $country = $validatedData['country'];

        $details = [
            'session_id' => session()->getId(),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'country' => $country,
            'age' => $age,
            'task' => 'FaultCat',
        ];

        $success = MicrotaskDemographics::create($details);

        // Store details in session, so as to not ask this same user again.
        if ($success) {
            $request->session()->put('faultcat.age', $age);
            $request->session()->put('faultcat.country', $country);
        }

        // Success or failure, let them carry on.
        return redirect()->action([\App\Http\Controllers\FaultcatController::class, 'index']);
    }

    public function status(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = null;
        }

        $Faultcat = new Faultcat;
        $data = $Faultcat->fetchStatus();

        return view('faultcat.status', [
            'status' => $data,
            'user' => $user,
        ]);
    }
}
