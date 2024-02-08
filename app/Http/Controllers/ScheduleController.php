<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ScheduleResource;
use App\Models\Message;
use App\Models\MessageType;
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
        $schedules = Schedule::Where('user_id', $userId)->latest()->get();
        
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
                'contact_id' => 'required_without:contactGroupId', 
                'contactGroupId' => 'required_without:contact_id',
                'customMessage' => 'required_without:messageId', 
                'messageId' => 'required_without:customMessage'
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
        if($request->contactGroupId){
            $schedule->contact_group_id = $request->contactGroupId;
        }     
        if($request->customMessage){
            $schedule->custom_message = $request->customMessage;
        }
        if($request->messageType){
            $schedule->message_type = $request->messageType;
        }
        if($request->messageId){
            $schedule->message_id = $request->messageId;
        }
        if($request->autoSend == 1){
            $schedule->auto_send = true;
        }else{
            $schedule->auto_send = false;
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


    public function getMessages(Request $request) 
    {   
        // \App\IncidenceOpration::join('offices','offices.id','incidenceoprations.branch_id')
        //         //->join('departments','departments.id','')
        //         ->join('offences','offences.id','incidenceoprations.offence_id')
        //         ->join('users','users.id','incidenceoprations.staff_id')
        //         ->where('incidenceoprations.staff_id',$user_id)
        //         ->select('users.*','offices.name as officename','offences.name as offencename','offences.amount','incidenceoprations.comment','incidenceoprations.created_at as date','incidenceoprations.status as offenceStatus')
        //         ->get();
        $messages = Message::where('type', $request->messageType)->latest()->get();
        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => $messages
        ]); 
    }

    public function getMessageTypes() 
    {   
        $messageTypes = MessageType::latest()->get();
        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => $messageTypes
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
        $schedule = Schedule::where('id', $id);
        if($schedule->exists()){
            $scheduleRow = $schedule->first();
            return response()->json([
                'status' => 200,
                'message' => 'success',
                'data' => $scheduleRow
            ]); 
        }else{
            return response()->json([
                'status' => 300,
                'message' => 'Failed',
                'data' => 'invalid schedule'
            ]); 
        }
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
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:100'],
            'duration' => ['required', 'string'],
            'start_at' => ['required', 'after_or_equal:now'],
            'contact_id' => 'required_without:contactGroupId', 
            'contactGroupId' => 'required_without:contact_id',
            'customMessage' => 'required_without:messageId', 
            'messageId' => 'required_without:customMessage'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'required_fields' => $validator->errors()->all(),
                'message' => 'Missing field(s) | Validation Error',
                'status' => '500'
            ]);
        }
        Log::info($request);
        $schedule = Schedule::where('id', $id);
        if($schedule->exists()){
            $schedule->update([
                'title' => $request->title,
                'duration' => $request->duration,
                'start_at' => Carbon::parse($request->start_at),
                'user_id' => Auth::id(),
            ]);

            if($request->contact_id){
                $schedule->update([
                    'contact_id' => $request->contact_id,
                ]);
            }

            if($request->contactGroupId){
                $schedule->update([
                    'contact_group_id' => $request->contactGroupId,
                ]);
            }

            if($request->customMessage){
                $schedule->update([
                    'custom_message' => $request->customMessage,
                ]);
            }

            if($request->messageType){
                $schedule->update([
                    'message_type' => $request->messageType,
                ]);
            }

            if($request->messageId){
                $schedule->update([
                    'message_id' => $request->messageId,
                ]);
            }

            if($request->autoSend == 1){
                $schedule->update([
                    'auto_send' => true,
                ]);
            }else{
                $schedule->update([
                    'auto_send' => false,
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Reminder Successfully Updated',
            ]); 
        }else{
            return response()->json([
                'status' => 300,
                'message' => 'Failed to Update Reminder',
            ]); 
        }


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function notify(){
        $now = Carbon::now();
        $theDataCheck = Schedule::where('status', 0)->whereDate('date', '<=', $now);
        if($theDataCheck->exists()){
            $theData = $theDataCheck->get();

        }
    }
}
