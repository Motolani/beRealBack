<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Kreait\Laravel\Firebase\Facades\Firebase;    
use Kreait\Firebase\Messaging\CloudMessage;

class FirebasePushController extends Controller
{
    //
    protected $notification;
    public function __construct()
    {
        $this->notification = Firebase::messaging();
    }

    public function setToken(Request $request)
    {
        $token = $request->input('fcm_token');
        $userId = Auth::id();
        Log::info('userId');
        Log::info($userId);
        $user = User::where('id', $userId);

        $user->update([
            'fcm_token' => $token
        ]); 
        return response()->json([
            'message' => 'Successfully Updated FCM Token'
        ]);
    }

    public function notification(Request $request)
    {
        $userId = Auth::id();
        Log::info('userId');
        Log::info($userId);
        $user = User::where('id', $userId)->first();

        $FcmToken = $user->fcm_token;
        //put in the appropriate body from the db
        $title = $request->input('title');
        $body = $request->input('body');
        $message = CloudMessage::fromArray([
        'token' => $FcmToken,
        'notification' => [
            'title' => $title,
            'body' => $body
            ],
        ]);

    $this->notification->send($message);
    }
}
