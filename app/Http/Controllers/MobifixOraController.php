<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\MobifixOra;
use App\Helpers\Microtask;

class MobifixOraController extends Controller {

    /**
     * Get random record.
     * Post opinion.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $partner = $request->input('partner', NULL);
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = Microtask::getAnonUserCta($request);
            if ($user->action) {
                return redirect()->action('MobifixOraController@cta', ['partner' => $partner]);
            }
        }
        if ($request->has('id-ords')) {
            $insert = [
                'id_ords' => $request->input('id-ords'),
                'fault_type_id' => $request->input('fault-type-id'),
                'user_id' => $user->id,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'session_id' => session()->getId(),
            ];
            $MobifixOra = new MobifixOra;
            $success = $MobifixOra->create($insert);
            if (!$success) {
                logger(print_r($insert, 1));
                logger('MobifixOra error on insert.');
            }
        }
        $fault = $this->_fetchRecord($request);
        if (!$fault) {
            return redirect()->action('MobifixOraController@status')->withSuccess('done');
        }
        $fault->translate = rawurlencode($fault->problem);
        $MobifixOra = new MobifixOra;
        $fault_types = $MobifixOra->fetchFaultTypes();
        $fault->suggestions = [];
        // match problem terms with suggestions
        foreach ($fault_types as $k => $v) {
            if (!empty($v->regex) && preg_match('/' . $v->regex . '/', strtolower($fault->translation), $matches)) {
                $fault->suggestions[$k] = $fault_types[$k];
            }
        }
        // send non-suggested fault_types to view
        $fault->faulttypes = array_diff_key($fault_types, $fault->suggestions);
        return view('mobifixora.index', [
            'title' => 'MobiFixORA',
            'fault' => $fault,
            'user' => $user,
            'partner' => $partner,
        ]);
    }

    /**
     * Fetch "call to action".
     *
     * @param Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function cta(Request $request) {
        return $this->index($request);
    }

    /**
     * Fetch current task statistics with optional partner filter.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request) {
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = Microtask::getAnonUserCta($request);
        }
        $partner = $request->input('partner', NULL);
        $MobifixOra = new MobifixOra;
        $data = $MobifixOra->fetchStatus($partner);
        $complete = $data['total_opinions_2'][0]->total + $data['total_opinions_1'][0]->total + $data['total_opinions_0'][0]->total == 0;
        return view('mobifixora.status', [
            'title' => 'MobiFixORA',
            'status' => $data,
            'user' => $user,
            'complete' => $complete,
            'partner' => $request->input('partner', NULL),
        ]);
    }

    /**
     * Fetch random record with optional partner filter and exclusions.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return object
     */
    protected function _fetchRecord(Request $request) {

//        $request->session()->flush();
        $result = FALSE;
        $partner = $request->input('partner', NULL);
        $exclusions = $request->session()->get('mobifixora.exclusions', []);
        $Mobifixora = new MobifixOra;
        $fault = $Mobifixora->fetchFault($exclusions, $partner);
        if ($fault) {
            $result = $fault[0];
            $request->session()->push('mobifixora.exclusions', $result->id_ords);
        }
        return $result;
    }

}
