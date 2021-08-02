<?php

namespace App\Http\Controllers;

use App\Device;
use App\Group;
use App\Party;
use Auth;
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
