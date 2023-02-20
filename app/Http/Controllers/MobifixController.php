<?php

namespace App\Http\Controllers;

use App\Helpers\Microtask;
use App\Mobifix;
use Auth;
use Illuminate\Http\Request;

class MobifixController extends Controller
{
    /**
     * Fetch / post random misc.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return redirect()->action([\App\Http\Controllers\MobifixController::class, 'status'])->withSuccess('done');
    }

    public function cta(Request $request)
    {
        return $this->index($request);
    }

    public function status(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = null;
        }

        $Mobifix = new Mobifix;
        $data = $Mobifix->fetchStatus();
        $complete = $data['total_opinions_2'][0]->total + $data['total_opinions_1'][0]->total + $data['total_opinions_0'][0]->total == 0;

        return view('mobifix.status', [
            'status' => $data,
            'user' => $user,
            'complete' => $complete,
        ]);
    }
}
