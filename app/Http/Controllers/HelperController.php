<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HelperController extends Controller
{
    //
    public function triggerScheduleForOneTime()
    {
        $userId = Auth::id();
        $usersScheduleCheck = Schedule::Where('user_id', $userId)->where('status', 0)->where('duration', 'Once')->where('start_at', '>=', Carbon::now());
        if($usersScheduleCheck->exists()){
            $usersSchedules = $usersScheduleCheck->get();

            $usersScheduleCheck->update([
                'status' => 2,
                'ended_at' => Carbon::now(),
            ]);

            return response()->json([
                'status' => 200,
                'data' => $usersSchedules,
                'message' => 'Successful'
            ]);
        }else{
            Log::info('nothing to notify in Once Off');
            //for contact group
            //we have to do two calls because i need to save the selected contact soit doesnt repeat until its been maxed out;
        }
    }

    public function triggerScheduleForReoccurring()
    {
        $userId = Auth::id();
        $usersScheduleCheck = Schedule::Where('user_id', $userId)->where('status', 0)->where('duration', '!=', 'Once')->where('start_at', '>=',Carbon::now());
        if($usersScheduleCheck->exists()){
            $usersSchedules = $usersScheduleCheck->get();

            $usersScheduleCheck->update([
                'status' => 1,
            ]);

            return response()->json([
                'status' => 200,
                'data' => $usersSchedules,
                'message' => 'Successful'
            ]);
        }else{
            Log::info('nothing to notify in Once Off');
            //for contact group
            //we have to do two calls because i need to save the selected contact soit doesnt repeat until its been maxed out;
        }

        
    }

}
