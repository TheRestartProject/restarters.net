<?php

namespace App\Http\Controllers;

use App;
use App\Helpers\Microtask;
use App\MicrotaskSurvey;
use App\DustupOra;
use Auth;
use Illuminate\Http\Request;

class DustupOraController extends Controller
{
    protected $Model;

    protected $MaxSignposts = 4;

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
        // DustUp is now closed.
        // return redirect()->action('DustupOraController@status');

        // For dev testing session.
        // $request->session()->flush();

        // We record that we have visited this page, so that if we subsequently sign up, we can redirect back to it.
        // This is an intentionally partial solution to the problem of redirecting after we log in.
        $request->session()->put('redirectTime', time());
        $request->session()->put('redirectTo', $request->path());

        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = Microtask::getAnonUserCta($request);
            if ($user->action) {
                return redirect()->action('DustupOraController@cta');
            }
        }

        $this->Model = new DustupOra;
        $signpost = false;
        // If opinion is being submitted.
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
            $this->Model = new DustupOra;
            $success = $this->Model->create($insert);
            if (! $success) {
                logger('DustUp error on insert.');
                logger(print_r($insert, 1));
            }
            $submits = $this->_getSubmits($request, $user);
            if ($submits < $this->MaxSignposts) {
                $signpost = $submits;
            } elseif ($submits == $this->MaxSignposts) {
                if ($user->id > 0) {
                    // logged-in user gets an extra signpost
                    $signpost = $submits;
                }
            }
        }
        $fault = $this->_fetchRecord($request);
        if (! $fault) {
            return redirect()->action('DustupOraController@status')->withSuccess('done');
        }
        $progress = $this->Model->fetchProgress()[0]->total;

        // @ToDo language handling
        // $fault->translate = rawurlencode($fault->problem);

        $fault->faulttypes = $this->Model->fetchFaultTypes();

        // @ToDo suggestions (if required)
        // $fault->suggestions = [];
        // match problem terms with suggestions
        // foreach ($fault_types as $k => $v) {
        //     if (! empty($v->regex) && preg_match('/'.$v->regex.'/', strtolower($fault->googletrans), $matches)) {
        //         $fault->suggestions[$k] = $fault_types[$k];
        //     }
        // }
        // send non-suggested fault_types to view
        // $fault->faulttypes = array_diff_key($fault_types, $fault->suggestions);

        // send the "poor data" fault_type to view so it can be styled separately
        $poor_data = $this->Model->fetchFaultTypePoorData();
        return view('dustupora.index', [
            'title' => 'DustUp',
            'fault' => $fault,
            'poor_data' => $poor_data,
            'user' => $user,
            'progress' => $progress > 1 ? $progress : 0,
            'signpost' => $signpost,
            'max_signposts' => $this->MaxSignposts,
            // 'thankyou' => $thankyou,
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
        $this->Model = new DustupOra;
        $data = $this->Model->fetchStatus();

        return view('dustupora.status', [
            'title' => 'DustUp',
            'status' => $data,
            'user' => $user,
            'complete' => ($data['progress'][0]->total == 100),
            'closed' => false,
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
     * Record submission clicks.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return int
     */
    protected function _getSubmits($request)
    {
        $submits = $request->session()->get('dustupora.submits', 0);
        $request->session()->put('dustupora.submits', ++$submits);

        return $submits;
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
     * Fetch a repair record.
     * Priority given to records with same language as user.
     * Previously seen session records are excluded.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return object
     */
    protected function _fetchRecord(Request $request)
    {
        $result = false;
        $exclusions = $request->session()->get('dustupora.exclusions', []);
        $this->Model = new DustupOra;
        $locale = $this->_getUserLocale();
        $fault = $this->Model->fetchFault($exclusions, $locale);
        if ($fault) {
            $result = $fault[0];
            $request->session()->push('dustupora.exclusions', $result->id_ords);
        }

        return $result;
    }
}
