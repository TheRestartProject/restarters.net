<?php

namespace App\Http\Controllers;

use App\Drip;
use Illuminate\Http\Request;

class DripController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $test = Drip::getAccounts();
    //   api_token
    // account_id
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Drip  $drip
     * @return \Illuminate\Http\Response
     */
    public function show(Drip $drip)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Drip  $drip
     * @return \Illuminate\Http\Response
     */
    public function edit(Drip $drip)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Drip  $drip
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Drip $drip)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Drip  $drip
     * @return \Illuminate\Http\Response
     */
    public function destroy(Drip $drip)
    {
        //
    }
}
