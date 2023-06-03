<?php

namespace App\Http\Controllers;

use App\Models\ContactGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactsController extends Controller
{
    //
    public function createGroup(Request $request)
    {
        # code...

        // Log::info($request);
        // Log::info('create group');

        $validator = Validator::make($request->all(), [
            'groupName'  => ['required','unique:contact_groups,name'],
            'selectedContacts' => ['required'],
            'userId' => ['required'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'required_fields' => $validator->errors()->all(),
                    'message' => 'Missing field(s) | Validation Error',
                    'status' => '500'
                ]);
            }

        $userIds = [];
        $groupName = $request->groupName;
        foreach($request->selectedContacts as $contact)
        {
                Log::info('here');
                Log::info($contact['recordID']);
                array_push($userIds, $contact['recordID']);
        }
        Log::info($userIds);
        // Log::info(sizeof($userIds));

        $newGroup = new ContactGroup();
        $newGroup->name = $groupName;
        $newGroup->contact_ids = json_encode($userIds);
        $newGroup->contact_count = sizeof($userIds);  
        $newGroup->user_id = Auth::id();  
        $saved = $newGroup->save();

        if($saved){
            return response()->json([
                'status' => 200,
                'message' => 'Successful'
            ]);
        }else{
            return response()->json([
                'status' => 300,
                'message' => 'Failed'
            ]); 
        }
    }

    public function getGroups(Request $request)
    {
        # code...
        Log::info($request);
        $contacts = ContactGroup::where('user_id', $request->userId)->get();
        return response()->json([
            'status' => 200,
            'data' => $contacts,
            'message' => 'success'
        ]); 

    }
    
}
