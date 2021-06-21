<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App;
use App\BattcatOra;
use App\MicrotaskSurvey;

class BattcatOraController extends Controller
{

    protected $Model;

    /**
     * Get random record.
     * Post opinion.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = $this->_anon();
        }
        // if survey is being submitted
        if ($request->has('task-survey')) {
            $inputs = $request->all();
            unset($inputs['_token']);
            unset($inputs['task-survey']);
            $payload = json_encode($inputs);
            $insert = [
                'task' => 'BattCat',
                'payload' => $payload,
                'user_id' => $user->id,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'session_id' => session()->getId(),
            ];
            $MicrotaskSurvey = new MicrotaskSurvey;
            $success = $MicrotaskSurvey->create($insert);
            if (!$success) {
                logger('MicrotaskSurvey error on insert.');
                logger(print_r($insert, 1));
            }
        }

        $this->Model = new BattcatOra;
        $signpost = FALSE;
        // if opinion is being submitted
        if ($request->has('id-ords')) {
            if (!(is_numeric($request->input('fault-type-id')) && $request->input('fault-type-id') > 0)) {
                return redirect()->back()->withErrors(['Oops, there was an error, please try again, sorry! If this error persists please contact The Restart Project.']);
            }
            $insert = [
                'id_ords' => $request->input('id-ords'),
                'fault_type_id' => $request->input('fault-type-id'),
                'user_id' => $user->id,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'session_id' => session()->getId(),
            ];
            $this->Model = new BattcatOra;
            $success = $this->Model->create($insert);
            if (!$success) {
                logger('BattCat error on insert.');
                logger(print_r($insert, 1));
            }
            $submits = $this->_getSubmits($request, $user);
            if ($submits < 5) {
                $signpost = $submits;
            } else if ($submits == 5) {
                if ($user->id == 0) {
                    // guest is redirected to modal survey
                    return redirect()->action('BattcatOraController@survey');
                } else {
                    // logged-in user gets an extra signpost
                    $signpost = $submits;
                }
            }
        }
        // final "thank you" signpost after survey whether submitted or not
        if ($request->session()->get('battcatora.redirected_from_survey', FALSE)) {
            $request->session()->put('battcatora.redirected_from_survey', FALSE);
            $signpost = 6;
        }
        // no signpost when showing survey
        if ($request->session()->get('battcatora.redirect_to_survey', FALSE)) {
            $request->session()->put('battcatora.redirect_to_survey', FALSE);
            $request->session()->put('battcatora.redirected_from_survey', TRUE);
        }
        $fault = $this->_fetchRecord($request);
        if (!$fault) {
            return redirect()->action('BattcatOraController@status')->withSuccess('done');
        }

        $fault->translate = rawurlencode($fault->problem);
        $fault_types = $this->Model->fetchFaultTypes($fault->repair_status);
        $fault->suggestions = [];
        // match problem terms with suggestions
        foreach ($fault_types as $k => $v) {
            if (!empty($v->regex) && preg_match('/' . $v->regex . '/', strtolower($fault->translation), $matches)) {
                $fault->suggestions[$k] = $fault_types[$k];
            }
        }
        // send non-suggested fault_types to view
        $fault->faulttypes = array_diff_key($fault_types, $fault->suggestions);
        // send the "poor data" fault_type to view
        // $poor_data = $this->Model->fetchFaultType();
        logger(print_r($poor_data,1));
        return view('battcatora.index', [
            'title' => 'BattCat',
            'fault' => $fault,
            // 'poor_data' => $poor_data,
            'user' => $user,
            'signpost' => $signpost,
            'locale' => $this->_getUserLocale(),
        ]);
    }

    /**
     * Fetch current task statistics.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = $this->_anon();
        }
        $this->Model = new BattcatOra;
        $data = $this->Model->fetchStatus();
        return view('battcatora.status', [
            'title' => 'BattCat',
            'status' => $data,
            'user' => $user,
            'complete' => FALSE, // ($data['progress'][0]->total == 100),
        ]);
    }

    /**
     * Fetch "call to action".
     *
     * @param Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function cta(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Fetch survey modal.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function survey(Request $request)
    {
        $request->session()->put('battcatora.redirect_to_survey', TRUE);
        return $this->index($request);
    }

    /**
     * Fetch random record.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return integer
     */
    protected function _getSubmits($request)
    {
        $submits = $request->session()->get('battcatora.submits', 0);
        $request->session()->put('battcatora.submits', ++$submits);
        return $submits;
    }

    /**
     * Fetch mock user record for anonymous user.
     *
     * @return object
     */
    protected function _anon()
    {
        $user = new \stdClass();
        $user->id = 0;
        $user->name = 'Guest';
        return $user;
    }

    /**
     * Fetch user locale string.
     *
     * @return string
     */
    protected function _getUserLocale()
    {
        return substr(App::getLocale(), 0, 2);
    }

    /**
     * Fetch random record.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return object
     */
    protected function _fetchRecord(Request $request)
    {
        // $request->session()->flush();
        $result = FALSE;
        $exclusions = $request->session()->get('battcatora.exclusions', []);
        $this->Model = new BattcatOra;
        $locale = $this->_getUserLocale();
        $fault = $this->Model->fetchFault($exclusions, $locale);
        if ($fault) {
            $result = $fault[0];
            $request->session()->push('battcatora.exclusions', $result->id_ords);
        }
        return $result;
    }
}
