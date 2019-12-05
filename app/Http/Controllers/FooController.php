<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Foo;
use Session;

class FooController extends Controller {

    /**
     * Fetch / post random fault.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {

        if (Auth::check()) {
            $user = Auth::user();
            $user->path = '/uploads/thumbnail_' . $user->path;
        } else {
            $user = new \stdClass();
            $user->id = 0;
            $user->name = "Guest";
            $user->path = "foo/guest.png";
        }
        if ($request->isMethod('post') && !empty($_POST)) {
            if (isset($_POST['iddevices']) && !isset($_POST['fetch'])) {

                $data = array_filter($_POST);
                $Foo = new Foo;
                $insert = [
                    'iddevices' => $data['iddevices'],
                    'fault_type' => $data['fault_type'],
                    'user_id' => $user->id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'session_id' => session()->getId(),
                ];
                $success = $Foo->create($insert);
                if (!$success) {
                    logger(print_r($insert, 1));
                    logger('FaultCat error on insert.');
                }
            }
        }
        $Foo = new Foo;
        $fault = $Foo->fetchFault()[0];
        $fault->category = preg_replace('/Laptop (small|medium|large)/', 'Laptop', $fault->category);
        $fault->translate = rawurlencode($fault->problem);

        return view('foo_faultcat.index', [
            'fault' => $fault,
            'user' => $user,
        ]);
    }

}
