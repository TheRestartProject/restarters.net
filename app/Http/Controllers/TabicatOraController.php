<?php

namespace App\Http\Controllers;

use App;
use App\MicrotaskSurvey;
use App\TabicatOra;
use Auth;
use Illuminate\Http\Request;

class TabicatOraController extends Controller
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
        // TabiCat is now closed.
        return redirect()->action('TabicatOraController@status');

        // We record that we have visited this page, so that if we subsequently sign up, we can redirect back to it.
        // This is an intentionally partial solution to the problem of redirecting after we log in.
        $request->session()->put('redirectTime', time());
        $request->session()->put('redirectTo', $request->path());

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
                'task' => 'TabiCat',
                'payload' => $payload,
                'user_id' => $user->id,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'session_id' => session()->getId(),
            ];
            $MicrotaskSurvey = new MicrotaskSurvey;
            $success = $MicrotaskSurvey->create($insert);
            if (! $success) {
                logger('MicrotaskSurvey error on insert.');
                logger(print_r($insert, 1));
            }
        }

        $this->Model = new TabicatOra;
        $signpost = false;
        // if opinion is being submitted
        if ($request->has('id-ords')) {
            if (! (is_numeric($request->input('fault-type-id')) && $request->input('fault-type-id') > 0)) {
                return redirect()->back()->withErrors(['Oops, there was an error, please try again, sorry! If this error persists please contact The Restart Project.']);
            }
            $insert = [
                'id_ords' => $request->input('id-ords'),
                'fault_type_id' => $request->input('fault-type-id'),
                'user_id' => $user->id,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'session_id' => session()->getId(),
            ];
            $this->Model = new TabicatOra;
            $success = $this->Model->create($insert);
            if (! $success) {
                logger('TabiCat error on insert.');
                logger(print_r($insert, 1));
            }
            $submits = $this->_getSubmits($request, $user);
            if ($submits < 5) {
                $signpost = $submits;
            } elseif ($submits == 5) {
                if ($user->id == 0) {
                    // guest is redirected to modal survey
                    return redirect()->action('TabicatOraController@survey');
                } else {
                    // logged-in user gets an extra signpost
                    $signpost = $submits;
                }
            }
        }
        // final "thank you" signpost after survey whether submitted or not
        if ($request->session()->get('tabicatora.redirected_from_survey', false)) {
            $request->session()->put('tabicatora.redirected_from_survey', false);
            $signpost = 6;
        }
        // no signpost when showing survey
        if ($request->session()->get('tabicatora.redirect_to_survey', false)) {
            $request->session()->put('tabicatora.redirect_to_survey', false);
            $request->session()->put('tabicatora.redirected_from_survey', true);
        }
        $fault = $this->_fetchRecord($request);
        if (! $fault) {
            return redirect()->action('TabicatOraController@status')->withSuccess('done');
        }

        $fault->translate = rawurlencode($fault->problem);
        $fault_types = $this->Model->fetchFaultTypes();
        $fault->suggestions = [];
        // match problem terms with suggestions
        foreach ($fault_types as $k => $v) {
            if (! empty($v->regex) && preg_match('/'.$v->regex.'/', strtolower($fault->translation), $matches)) {
                $fault->suggestions[$k] = $fault_types[$k];
            }
        }
        // send non-suggested fault_types to view
        $fault->faulttypes = array_diff_key($fault_types, $fault->suggestions);
        // send the "poor data" fault_type to view
        $poor_data = $this->Model->fetchFaultTypePoorData();
        logger(print_r($poor_data, 1));

        return view('tabicatora.index', [
            'title' => 'TabiCat',
            'fault' => $fault,
            'poor_data' => $poor_data,
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
        $this->Model = new TabicatOra;
        $data = $this->Model->fetchStatus();

        return view('tabicatora.status', [
            'title' => 'TabiCat',
            'status' => $data,
            'user' => $user,
            'complete' => ($data['progress'][0]->total == 100),
            'closed' => true,
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
        $request->session()->put('tabicatora.redirect_to_survey', true);

        return $this->index($request);
    }

    /**
     * Fetch random record.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return int
     */
    protected function _getSubmits($request)
    {
        $submits = $request->session()->get('tabicatora.submits', 0);
        $request->session()->put('tabicatora.submits', ++$submits);

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
        $result = false;
        $exclusions = $request->session()->get('tabicatora.exclusions', []);
        $this->Model = new TabicatOra;
        $locale = $this->_getUserLocale();
        $fault = $this->Model->fetchFault($exclusions, $locale);
        if ($fault) {
            $result = $fault[0];
            $request->session()->push('tabicatora.exclusions', $result->id_ords);
        }

        return $result;
    }
}
