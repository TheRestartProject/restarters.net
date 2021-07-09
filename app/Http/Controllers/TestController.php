<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class TestController extends Controller {

    /**
     * Nothing to see here.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {

        return redirect()->action('HomeController@index');

    }

    public function styles(Request $request) {

        if (!Auth::check()) {
            return redirect()->action('HomeController@index');
        }

        return view('test.styles', []);
    }

}
