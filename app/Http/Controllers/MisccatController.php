<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Misccat;
use App\Helpers\Microtask;

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
            $user = Microtask::getAnonUserCta($request);
//            logger(print_r($user,1));
            if ($user->action) {                
                return redirect()->action('MisccatController@cta');
            }
        }

        if ($request->isMethod('post') && !empty($_POST)) {
            if (isset($_POST['iddevices'])) {
                $data = $_POST;
                $Misccat = new Misccat;
                $insert = [
                    'iddevices' => $data['iddevices'],
                    'category' => $data['category'],
                    'eee' => $data['eee'],
                    'user_id' => $user->id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'session_id' => session()->getId(),
                ];
                $success = $Misccat->create($insert);
                if (!$success) {
                    logger(print_r($insert, 1));
                    logger('MiscCat error on insert.');
                }
            }
        }
        $Misccat = new Misccat;
        $misc = $Misccat->fetchMisc()[0];
        $misc->translate = rawurlencode($misc->problem);

        return view('misccat.index', [
            'misc' => $misc,
            'user' => $user,
        ]);
    }

    public function cta(Request $request) {
        return $this->index($request);
    }
    
    public function status(Request $request) {
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = null;
        }

        $Misccat = new Misccat;
        $data = $Misccat->fetchStatus();
        return view('misccat.status', [
            'status' => $data,
            'user' => $user,
        ]);
    }
    
}
