<?php

namespace App\Http\Controllers;

use App;
use Auth;
use Illuminate\Http\Request;
use App\Helpers\Microtask;
use App\DustupOra;

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
         return redirect()->action('DustupOraController@status');
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
}
