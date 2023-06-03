<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function durationOptions(Request $request)
    {
        $durations = DB::table('duration_types')->get();
        return $durations;
    }
}
