<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Misccat;
use Session;
use Carbon\Carbon;

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
//            $request->session()->flush();        
            $user->clicks = $request->session()->get('misccat.counter', 0);
            $user->cta = $request->session()->get('misccat.cta', 0);
            $user->sesh = $request->session()->get('misccat.sesh', 0);
            if (!$user->sesh) {
                $request->session()->put('misccat.sesh', Carbon::now()->timestamp);
                $user->sesh = $request->session()->get('misccat.sesh', 0);
            }
            logger(print_r($request->session()->all(), 1));
            logger(print_r($_SESSION, 1));
            if (!$user->cta) {
                logger('no cta');
                $request->session()->put('misccat.counter', ++$user->clicks);
                if ($user->clicks % 4 == 0) {
                    logger('time for cta');
                    $request->session()->put('misccat.cta', 1);
                    $request->session()->put('misccat.sesh', Carbon::now()->timestamp);
                    return redirect()->action('MisccatController@cta');
                }
            } else {
                logger('did cta: elapsed = ');
                logger(Carbon::now()->timestamp);
                logger(Carbon::now()->timestamp - $user->sesh);
                if ((Carbon::now()->timestamp - $user->sesh) > 60) {
                    logger('cta expired');
                    $request->session()->put('misccat.cta', 0);
                    $request->session()->put('misccat.counter', 0);
                    $request->session()->put('misccat.sesh', Carbon::now()->timestamp);
                }
            }
        }

        if ($request->isMethod('post') && !empty($_POST)) {
            if (isset($_POST['iddevices'])) {
                logger(print_r($_POST, 1));
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

    protected function getAnonUser(Request $request) {
        $user = new \stdClass();
        $user->id = 0;
        $user->name = 'Guest';
//            $request->session()->flush();
        $user->clicks = $request->session()->get('misccat.clicks', 0);
        if (!$user->clicks) {
            $request->session()->put('misccat.clicks', $user->clicks);
        }
        $user->cta = $request->session()->get('misccat.cta', 0);
        if (!$user->cta) {
            $request->session()->put('misccat.cta', $user->cta);
        }
        $user->sesh = $request->session()->get('misccat.sesh', 0);
        if (!$user->sesh) {            
            $user->sesh = Carbon::now()->timestamp;
            $request->session()->put('misccat.sesh', $user->sesh);
        }
//        logger(print_r($request->session()->all(), 1));
        if (!$user->cta) {
//            logger('no cta');
            $request->session()->put('misccat.clicks', ++$user->clicks);
            if ($user->clicks % 4 == 0) {
//                logger('time for cta');
                $request->session()->put('misccat.cta', 1);
                $request->session()->put('misccat.sesh', Carbon::now()->timestamp);
                return redirect()->action('MisccatController@cta');
            }
        } else {
//            logger('cta done: elapsed = ');
//            logger(Carbon::now()->timestamp - $user->sesh);
            if ((Carbon::now()->timestamp - $user->sesh) > 60) {
//                logger('cta expired');
                $request->session()->put('misccat.cta', 0);
                $request->session()->put('misccat.clicks', 0);
                $request->session()->put('misccat.sesh', Carbon::now()->timestamp);
            }
        }
        $user->clicks = $request->session()->get('misccat.clicks', 0);
        $user->cta = $request->session()->get('misccat.cta', 0);
        $user->sesh = $request->session()->get('misccat.sesh', 0);
        return $user;
    }

}
