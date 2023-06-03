<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $userId = Auth::id();
        $schedules = Schedule::Where('user_id', $userId)->get();
        
        return $schedules;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info($request);
        // $request->validated($request->all());
        $validator = Validator::make($request->all(), [
                'title' => ['required', 'string', 'max:100'],
                'duration' => ['required', 'string'],
                'start_at' => ['required', 'after_or_equal:now'],
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'required_fields' => $validator->errors()->all(),
                    'message' => 'Missing field(s) | Validation Error',
                    'status' => '500'
                ]);
            }
        Log::info(Auth::id());
        $start = Carbon::parse($request->start_at);

        Log::info($start);

        $schedule = new Schedule();
        $schedule->title = $request->title;
        $schedule->user_id = Auth::id();
        $schedule->duration = $request->duration;
        $schedule->start_at = $start;
        if($request->contact_id){
            $schedule->contact_id = $request->contact_id;
        }
        if($request->contact_group_id){
            $schedule->contact_group_id = $request->contact_group_id;
        }
        if($request->message_id){
            $schedule->message_id = $request->message_id;
        }
        $schedule->save();

        $thisSchedule = Schedule::join('users', 'schedules.user_id', 'users.id')
        ->where('schedules.id', $schedule->id)
        ->select('schedules.*', 'users.name', 'users.email', 'users.created_at as user_created_at')
        ->first();
        $theNewSchedule = new ScheduleResource($thisSchedule);

        // dd($theNewSchedule);
        // return $theNewSchedule;
        return response()->json([
            'status' => 200,
            'message' => 'Successful'
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    public function test(Request $request)
    {
        //
        Log::info($request);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
