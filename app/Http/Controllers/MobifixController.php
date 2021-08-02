<?php

namespace App\Http\Controllers;

use App\Helpers\Microtask;
use App\Mobifix;
use Auth;
use Illuminate\Http\Request;

class MobifixController extends Controller
{
    /**
     * Fetch / post random misc.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return redirect()->action('MobifixController@status')->withSuccess('done');

        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = Microtask::getAnonUserCta($request);
            if ($user->action) {
                return redirect()->action('MobifixController@cta');
            }
        }

        if ($request->isMethod('post') && ! empty($_POST) && (isset($_POST['iddevices']))) {
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
            if (! $success) {
                logger(print_r($insert, 1));
                logger('Mobifix error on insert.');
            }
        }
        $Mobifix = new Mobifix;
        $faultResult = $Mobifix->fetchFault();
        if (! empty($faultResult)) {
            $fault = $faultResult[0];
        } else {
            $fault = false;
        }
        if (! $fault) {
            return redirect()->action('MobifixController@status');
        }
        $fault->translate = rawurlencode($fault->problem);
        // match problem terms with suggestions
        $suggestions = $this->_suggestions();
        $fault_types = $this->_faulttypes();
        $fault->descriptions = $this->_faultdescs();
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

    public function cta(Request $request)
    {
        return $this->index($request);
    }

    public function status(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = null;
        }

        $Mobifix = new Mobifix;
        $data = $Mobifix->fetchStatus();
        $complete = $data['total_opinions_2'][0]->total + $data['total_opinions_1'][0]->total + $data['total_opinions_0'][0]->total == 0;

        return view('mobifix.status', [
            'status' => $data,
            'user' => $user,
            'complete' => $complete,
        ]);
    }

    protected function _faulttypes()
    {
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

    protected function _faultdescs()
    {
        return [
            'Power/battery' => '',
            'Screen' => 'Fault involves screen assembly - glass, touch, LCD...',
            'Stuck booting' => 'Powers on but OS does not load/errors',
            'Camera' => '',
            'Headphone jack' => 'Broken, loose, dirty...',
            'Speaker/amplifier' => 'No sound, volume issues...',
            'Charger' => 'Problem with the charger not the phone itself',
            'On/Off button' => '',
            'Volume buttons' => '',
            'Other buttons' => '',
            'Software update' => 'Problem after update, lack of updates...',
            'Storage problem' => 'Run out of storage space, corrupted storage...',
            'USB/charging port' => 'Broken, loose, dirty...',
            'Sim card slot' => '',
            'Microphone' => '',
            'Bluetooth' => '',
            'Memory card slot' => '',
            'Unknown' => 'Not enough info to determine the main fault',
            'Other' => 'Main fault is known but there is no option for it',
        ];
    }

    protected function _suggestions()
    {
        return [
            'battery|power' => [
                'Power/battery',
            ],
            'button' => [
                'On/Off button',
                'Volume buttons',
                'Other buttons',
            ],
            'camera|lens|picture|photo|video' => [
                'Camera',
            ],
            'card|sim' => [
                'Sim card slot',
                'Memory card slot',
                'Storage problem',
            ],
            'start|boot' => [
                'Stuck booting',
                'Power/battery',
                'Software update',
            ],
            'switch' => [
                'Other buttons',
                'Power/battery',
            ],
            'cable|connector|port|usb' => [
                'USB/charging port',
            ],
            'memory|ram' => [
                'Memory card slot',
                'Software update',
                'Stuck booting',
            ],
            'storage|space|full' => [
                'Storage problem',
            ],
            'charg|plug' => [
                'Charger',
                'USB/charging port',
                'Power/battery',
            ],
            'mic' => [
                'Microphone',
            ],
            'app(s)?|software' => [
                'Software update',
                'Storage problem',
                'Stuck booting',
            ],
            'headphone|jack' => [
                'Headphone jack',
            ],
            'bluetooth' => [
                'Bluetooth',
            ],
            'sc(r)?een|display|touch|glass|lcd|reader|digiti' => [
                'Screen',
            ],
            ' off| on' => [
                'On/Off button',
                'Stuck booting',
            ],
            'sound|audio|speaker|volume' => [
                'Speaker/amplifier',
                'Headphone jack',
                'Volume buttons',
            ],
            'slow|virus|bricked' => [
                'Stuck booting',
                'Storage problem',
                'Software update',
            ],
            'update|reset' => [
                'Software update',
                'Stuck booting',
            ],
        ];
    }
}
