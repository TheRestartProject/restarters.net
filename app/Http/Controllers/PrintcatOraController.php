<?php

namespace App\Http\Controllers;

use App;
use App\Helpers\Microtask;
use App\PrintcatOra;
use Auth;
use Illuminate\Http\Request;

class PrintcatOraController extends Controller
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
        // PrintCat is now closed.
        return redirect()->action('PrintcatOraController@status');

        // We record that we have visited this page, so that if we subsequently sign up, we can redirect back to it.
        // This is an intentionally partial solution to the problem of redirecting after we log in.
        $request->session()->put('redirectTime', time());
        $request->session()->put('redirectTo', $request->path());

        $partner = $request->input('partner', null);
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = Microtask::getAnonUserCta($request);
            if ($user->action) {
                return redirect()->action('PrintcatOraController@cta', ['partner' => $partner]);
            }
        }
        $this->Model = new PrintcatOra;
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
            $this->Model = new PrintcatOra;
            $success = $this->Model->create($insert);
            if (! $success) {
                logger('PrintCat error on insert.');
                logger(print_r($insert, 1));
            }
        }
        $fault = $this->_fetchRecord($request);
        if (! $fault) {
            return redirect()->action('PrintcatOraController@status')->withSuccess('done');
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

        return view('printcatora.index', [
            'title' => 'PrintCat',
            'fault' => $fault,
            'user' => $user,
            'partner' => $partner,
            'locale' => $this->_getUserLocale(),
        ]);
    }

    protected function _getUserLocale()
    {
        return substr(App::getLocale(), 0, 2);
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
        $partner = $request->input('partner', null);
        $this->Model = new PrintcatOra;
        $data = $this->Model->fetchStatus($partner);
        $complete = $data['total_opinions_2'][0]->total + $data['total_opinions_1'][0]->total + $data['total_opinions_0'][0]->total == 0;

        return view('printcatora.status', [
            'title' => 'PrintCat',
            'status' => $data,
            'user' => $user,
            'complete' => $complete,
            'closed' => true,
            'partner' => $request->input('partner', null),
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
        $result = false;
        $partner = $request->input('partner', null);
        $exclusions = $request->session()->get('printcatora.exclusions', []);
        $this->Model = new PrintcatOra;
        $locale = $this->_getUserLocale();
        $fault = $this->Model->fetchFault($exclusions, $locale, $partner);
        if ($fault) {
            $result = $fault[0];
            $request->session()->push('printcatora.exclusions', $result->id_ords);
        }

        return $result;
    }
}
