<?php

namespace App\Http\Controllers;

use App\Device;
use App\Http\Controllers\Controller;
use App\Party;
use App\Helpers\Fixometer;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AboutController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function index()
    {
        $stats = Fixometer::loginRegisterStats();

        $slides = [];

        $slides[] = [
            'image' => '01--learn',
            'text' => 'Learn how to organise and volunteer at events',
        ];
        $slides[] = [
            'image' => '02--tech-help',
            'text' => 'Get technical help, on tools, safety and risk',
        ];
        $slides[] = [
            'image' => '03--manage-events',
            'text' => 'Announce an event, find people',
        ];
        $slides[] = [
            'image' => '04--host-event',
            'text' => 'Host an event and share skills',
        ];
        $slides[] = [
            'image' => '05--log-repairs',
            'text' => 'Log the repairs, to reveal impact and help future repairers',
        ];
        $slides[] = [
            'image' => '06--bring-down-barriers',
            'text' => 'Bring down the barriers to repair',
        ];

        $deviceCount = array_key_exists(0, $stats['device_count_status']) ? $stats['device_count_status'][0]->counter : 0;

        return view('features.index', [
            'slides' => $slides,
            'co2Total' => $stats['co2Total'][0]->total_footprints,
            'wasteTotal' => $stats['co2Total'][0]->total_weights,
            'partiesCount' => count($stats['allparties']),
            'deviceCount' => $deviceCount,
        ]);
    }
}
