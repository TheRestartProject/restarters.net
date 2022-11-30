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
        return redirect()->action([\App\Http\Controllers\PrintcatOraController::class, 'status']);
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
}
