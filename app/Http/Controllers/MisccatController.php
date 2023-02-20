<?php

namespace App\Http\Controllers;

use App\Helpers\Microtask;
use App\Misccat;
use Auth;
use Illuminate\Http\Request;

class MisccatController extends Controller
{
    /**
     * Fetch / post random misc.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return redirect()->action([\App\Http\Controllers\MisccatController::class, 'status'])->withSuccess('done');
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

        $Misccat = new Misccat;
        $data = $Misccat->fetchStatus();

        return view('misccat.status', [
            'status' => $data,
            'user' => $user,
        ]);
    }
}
