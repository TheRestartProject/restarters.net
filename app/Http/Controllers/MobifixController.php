<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Mobifix;
use App\Helpers\Microtask;

class MobifixController extends Controller {

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
                return redirect()->action('MobifixController@cta');
            }
        }

        if ($request->isMethod('post') && !empty($_POST)) {
            if (isset($_POST['iddevices'])) {
                $data = $_POST;
                $Mobifix = new Mobifix;
                $insert = [
                    'iddevices' => $data['iddevices'],
                    'fault_type' => $data['fault_type'],
                    'user_id' => $user->id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'session_id' => session()->getId(),
                ];
                $success = $Mobifix->create($insert);
                if (!$success) {
                    logger(print_r($insert, 1));
                    logger('Mobifix error on insert.');
                }
            }
        }
        $Mobifix = new Mobifix;
        $fault = $Mobifix->fetchFault()[0];
        $fault->translate = rawurlencode($fault->problem);
        // match problem terms with suggestions
        $suggestions = $this->_suggestions();
        $fault_types = $this->_faulttypes();
        $fault->suggestions = [];
        foreach ($suggestions as $term => $faults) {
            if (preg_match("/$term/", strtolower($fault->problem), $matches)) {
                $fault->suggestions = array_unique(array_merge($fault->suggestions, $faults));
                $fault_types = array_diff($fault_types, $faults);
            }
        }
        // send non-suggested fault_types to view
        $fault->faulttypes = $fault_types;
        return view('mobifix.index', [
            'fault' => $fault,
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

        $Mobifix = new Mobifix;
        $data = $Mobifix->fetchStatus();
        return view('mobifix.status', [
            'status' => $data,
            'user' => $user,
        ]);
    }

    protected function _faulttypes() {
        return [
            'Power/battery',
            'Screen',
            'Stuck booting',
            'Camera',
            'Headphone jack',
            'Speaker/amplifier',
            'Charger',
            'On/Off button',
            'Volume buttons',
            'Other buttons',
            'Software update',
            'Storage problem',
            'USB/charging port',
            'Sim card slot',
            'Microphone',
            'Bluetooth',
            'Memory card slot',
            'Unknown',
            'Other',
        ];
    }

    protected function _suggestions() {
        return [
            'battery' => [
                'Power/battery',
            ],
            'lcd' => [
                'Screen',
            ],
            'button' => [
                'On/Off button',
                'Volume buttons',
                'Other buttons',
            ],
            'camera' => [
                'Camera',
            ],
            'digiti' => [
                'Screen',
            ],
            'display' => [
                'Screen',
            ],
            'card' => [
                'Sim card slot',
                'Memory card slot',
                'Storage problem',
            ],
            'usb' => [
                'USB / charging port',
            ],
            'sim' => [
                'Sim card slot',
            ],
            'start' => [
                'Software update',
                'Stuck booting',
                'Power/battery',
            ],
            'switch' => [
                'Software update',
                'Stuck booting',
                'Other buttons',
            ],
            'boot' => [
                'Software update',
                'Stuck booting',
            ],
            'connector' => [
                'USB / charging port',
            ],
            'cable' => [
                'USB / charging port',
            ],
            'jack' => [
                'Headphone jack',
            ],
            'speaker' => [
                'Speaker/amplifier',
            ],
            'memory' => [
                'Memory card slot',
                'Stuck booting',
                'Software update',
            ],
            'storage' => [
                'Storage problem'
            ],
            'space' => [
                'Storage problem'
            ],
            'full' => [
                'Storage problem'
            ],
            'ram' => [
                'Memory card slot',
                'Stuck booting',
                'Software update',
            ],
            'glass' => [
                'Screen',
            ],
            'charg' => [
                'Charger',
                'USB / charging port',
                'Power/battery',
            ],
            'lens' => [
                'Camera',
            ],
            'mic' => [
                'Microphone',
            ],
            'audio' => [
                'Speaker/amplifier',
                'Volume buttons',
                'Headphone jack',
            ],
            'app(s)?' => [
                'Software update',
                'Storage problem',
                'Stuck booting',
            ],
            'headphone' => [
                'Headphone jack',
            ],
            'touchscreen' => [
                'Screen',
            ],
            'bluetooth' => [
                'Bluetooth',
            ],
            'reader' => [
                'Screen',
            ],
            'sc(r)?een' => [
                'Screen',
            ],
            'plug' => [
                'USB / charging port',
                'Charger',
            ],
            'bricked' => [
                'Software update',
                'Storage problem',
                'Stuck booting',
            ],
            'volume' => [
                'Volume buttons',
                'Speaker/amplifier',
                'Headphone jack',
            ],
            'off' => [
                'On/Off button',
                'Stuck booting',
            ],
            'port' => [
                'USB / charging port',
            ],
            'sound' => [
                'Speaker/amplifier',
                'Headphone jack',
                'Volume buttons',
            ],
            'power' => [
                'Power/battery',
            ],
            'slow' => [
                'Stuck booting',
                'Storage problem',
                'Software update',
            ],
            'virus' => [
                'Stuck booting',
                'Software update',
            ],
        ];
    }

}
