<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App;
use App\TabicatOra;
// use App\Helpers\Microtask;

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
        // $request->session()->flush();
        $signpost = FALSE;
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = new \stdClass();
            $user->id = 0;
            $user->name = 'Guest';
        }
        $this->Model = new TabicatOra;
        $signpost = FALSE;
        // if form submission
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
            $this->Model = new TabicatOra;
            $success = $this->Model->create($insert);
            if (!$success) {
                logger('TabiCat error on insert.');
                logger(print_r($insert, 1));
            }
            $signpost = $this->_getSignpost($request, $user);
        }
        $fault = $this->_fetchRecord($request);
        if (!$fault) {
            return redirect()->action('TabicatOraController@status')->withSuccess('done');
        }
        $fault->translate = rawurlencode($fault->problem);
        $fault_types = $this->Model->fetchFaultTypes();
        $fault->suggestions = [];
        // match problem terms with suggestions
        foreach ($fault_types as $k => $v) {
            if (!empty($v->regex) && preg_match('/' . $v->regex . '/', strtolower($fault->translation), $matches)) {
                $fault->suggestions[$k] = $fault_types[$k];
            }
        }
        // send non-suggested fault_types to view
        $fault->faulttypes = array_diff_key($fault_types, $fault->suggestions);
        return view('tabicatora.index', [
            'title' => 'TabiCat',
            'fault' => $fault,
            'user' => $user,
            'signpost' => $signpost,
            'locale' => $this->_getUserLocale(),
        ]);
    }

    protected function _getUserLocale()
    {
        return substr(App::getLocale(), 0, 2);
    }

    protected function _getSignpost($request, $user)
    {
        $signpost = FALSE;
        $submits = $request->session()->get('tabicatora.submits', 0);
        $request->session()->put('tabicatora.submits', ++$submits);
        logger('submits=' . $submits);
        if ($submits == 5) {
            if ($user->id == 0) {
                // guest is redirected to modal survey
                logger('redirecting to survey');
                return redirect()->action('TabicatOraController@survey');
            } else {
                // logged-in user gets an extra signpost
                logger('extra signpost');
                $signpost = $submits;
            }
        } else if ($submits < 5) {
            // any user gets up to 4 default signposts
            logger('default signpost');
            $signpost = $submits;
        }
        logger('signpost ' . $signpost);
        return $signpost;
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
        return $this->index($request);
    }

    /**
     * Fetch current task statistics with optional partner filter.
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
            $user = Microtask::getAnonUserCta($request);
        }
        $partner = $request->input('partner', NULL);
        $this->Model = new TabicatOra;
        $data = $this->Model->fetchStatus($partner);
        $complete = $data['total_opinions_2'][0]->total + $data['total_opinions_1'][0]->total + $data['total_opinions_0'][0]->total == 0;
        return view('tabicatora.status', [
            'title' => 'TabiCat',
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
    protected function _fetchRecord(Request $request)
    {

        //        $request->session()->flush();
        $result = FALSE;
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
