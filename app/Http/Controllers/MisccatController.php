<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Misccat;
use App\MicrotaskDemographics;
use Session;

class MisccatController extends Controller {

    /**
     * Fetch / post random misc.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = new \stdClass();
            $user->id = 0;
            $user->name = 'Guest';
        }

        $user->clicks = $request->session()->get('misccat.counter', 0);
        $request->session()->put('misccat.counter', ++$user->clicks);

        $user->country = $request->session()->get('misccat.country', null);
        $user->age = $request->session()->get('misccat.age', null);

        if (!$user->id && ($user->clicks % 4 == 0) && (!$user->country || !$user->age)) {
            return redirect()->action('MisccatController@demographics');
        }

        if ($request->isMethod('post') && !empty($_POST)) {
            if (isset($_POST['iddevices']) && !isset($_POST['fetch'])) {
                $data = array_filter($_POST);
                if (isset($_POST['country'])) {
                    $request->session()->put('misccat.country', $data['country']);
                } else {
                    $data['country'] = '';
                }
                if (isset($_POST['age'])) {
                    $request->session()->put('misccat.age', $data['age']);
                } else {
                    $data['age'] = '';
                }
                $Misccat = new Misccat;
                $insert = [
                    'iddevices' => $data['iddevices'],
                    'category' => $data['category'],
                    'eee' => $data['eee'],
                    'user_id' => $user->id,
//                    'ip_address' => $_SERVER['REMOTE_ADDR'],
//                    'session_id' => session()->getId(),
                ];
                logger(print_r($insert, 1));
                $success = $Misccat->create($insert);
                if (!$success) {
                    logger(print_r($insert, 1));
                    logger('MiscCat error on insert.');
                }
            }
        }
        $user->country = $request->session()->get('misccat.country', null);
        $user->age = $request->session()->get('misccat.age', null);

        $Misccat = new Misccat;
        $misc = $Misccat->fetchMisc()[0];
        $misc->translate = rawurlencode($misc->problem);

        return view('misccat.index', [
            'misc' => $misc,
            'user' => $user,
        ]);
    }

    public function demographics(Request $request) {
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = new \stdClass();
            $user->id = 0;
            $user->name = 'Guest';
        }

        return view('misccat.demographics', [
            'user' => $user,
        ]);
    }

    /**
     * Store demographic information from anonymous users.
     */
    public function storeDemographics(Request $request) {
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
            'task' => 'MiscCat',
        ];

        $success = MicrotaskDemographics::create($details);

        // Store details in session, so as to not ask this same user again.
        if ($success) {
            $request->session()->put('misccat.age', $age);
            $request->session()->put('misccat.country', $country);
        }

        // Success or failure, let them carry on.
        return redirect()->action('MisccatController@index');
    }

}
