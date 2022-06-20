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
