<?php

namespace App\Http\Controllers;

use App;
use App\BattcatOra;
use App\MicrotaskSurvey;
use Auth;
use Illuminate\Http\Request;

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
        // BattCat is now closed.
        return redirect()->action('BattcatOraController@status');

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
        $thankyou = false;
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
            if (! $success) {
                logger('MicrotaskSurvey error on insert.');
                logger(print_r($insert, 1));
            }
            $thankyou = 'guest';
        }

        $this->Model = new BattcatOra;
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
            $this->Model = new BattcatOra;
            $success = $this->Model->create($insert);
            if (! $success) {
                logger('BattCat error on insert.');
                logger(print_r($insert, 1));
            }
            $submits = $this->_getSubmits($request, $user);
            if ($submits == 5) {
                if ($user->id == 0) {
                    // guest is redirected to modal survey
                    return redirect()->action('BattcatOraController@survey');
                } else {
                    $thankyou = 'user';
                }
            }
        }
        $fault = $this->_fetchRecord($request);
        if (! $fault) {
            return redirect()->action('BattcatOraController@status')->withSuccess('done');
        }
        $progress = $this->Model->fetchProgress()[0]->total;
        $fault->faulttypes = $this->Model->fetchFaultTypes($fault->repair_status);

        return view('battcatora.index', [
            'title' => 'BattCat',
            'fault' => $fault,
            'user' => $user,
            'progress' => $progress > 1 ? $progress : 0,
            'thankyou' => $thankyou,
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
            'categories' => $this->getCategories(),
            'complete' => ($data['progress'][0]->total == 100),
            'closed' => true,
        ]);
    }

    protected function getCategories()
    {
        return [
            'Battery/charger/adapter',
            'Decorative or safety lights',
            'Desktop computer',
            'Digital compact camera',
            'DSLR/video camera',
            'Flat screen',
            'Food processor',
            'Games console',
            'Hair & beauty item',
            'Handheld entertainment device',
            'Headphones',
            'Hi-Fi integrated',
            'Hi-Fi separates',
            'Lamp',
            'Laptop',
            'Large home electrical',
            'Misc',
            'Mobile',
            'Musical instrument',
            'PC accessory',
            'Portable radio',
            'Power tool',
            'Printer/scanner',
            'Projector',
            'Sewing machine',
            'Small home electrical',
            'Small kitchen item',
            'Tablet',
            'Toy',
            'TV and gaming-related accessories',
            'Vacuum',
            'Watch/clock',
        ];
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
        $request->session()->put('battcatora.redirect_to_survey', true);

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
        $result = false;
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
