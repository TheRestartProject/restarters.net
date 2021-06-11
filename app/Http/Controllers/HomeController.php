<?php

namespace App\Http\Controllers;

use Auth;
use App\Device;
use App\Party;
use App\Group;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        } else {
            return redirect('/user/register');
        }
    }
}
