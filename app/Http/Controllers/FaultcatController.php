<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Faultcat;
use Session;

class FaultcatController extends Controller {

    /**
     * Fetch / post random fault.
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
        
        $user->clicks = $request->session()->get('faultcat.counter', 0);
        $request->session()->put('faultcat.counter', ++$user->clicks);
        
        if ($request->isMethod('post') && !empty($_POST)) {
            if (isset($_POST['iddevices']) && !isset($_POST['fetch'])) {
                $data = array_filter($_POST);
//                logger(print_r($data, 1));
                if (isset($_POST['country'])) {
                    $request->session()->put('faultcat.country', $data['country']);
                } else {
                    $data['country'] = '';
                }
                if (isset($_POST['age'])) {
                    $request->session()->put('faultcat.age', $data['age']);
                } else {
                    $data['age'] = '';
                }
                $Faultcat = new Faultcat;
                $insert = [
                    'iddevices' => $data['iddevices'],
                    'fault_type' => $data['fault_type'],
                    'user_id' => $user->id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'session_id' => session()->getId(),
                    'country' => $data['country'],
                    'age' => $data['age'],
                ];
                $success = $Faultcat->create($insert);
                if (!$success) {
                    logger(print_r($insert, 1));
                    logger('FaultCat error on insert.');
//                } else {
//                    logger(print_r($insert, 1));
                }
            }
        }
//        logger(print_r($request->session()->get('faultcat'), 1));
        $user->country = $request->session()->get('faultcat.country', null);
        $user->age = $request->session()->get('faultcat.age', null);        

        $Faultcat = new Faultcat;
        $fault = $Faultcat->fetchFault()[0];
        $fault->category = preg_replace('/Laptop (small|medium|large)/', 'Laptop', $fault->category);
        $fault->translate = rawurlencode($fault->problem);

        return view('faultcat.index', [
            'fault' => $fault,
            'user' => $user,
        ]);
    }

}
