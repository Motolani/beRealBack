<?php

namespace App\Http\Controllers;

use App\Models\ContactGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        Log::info('pwd');
        Log::info(Hash::make('password'));

        $contacts = ContactGroup::where('user_id', $request->userId)->where('soft_delete', 0)->latest()->get();
        return response()->json([
            'status' => 200,
            'data' => $contacts,
            'message' => 'success'
        ]); 

    }

    public function getGroupDetails(Request $request)
    {
        # code...
        Log::info($request);
        Log::info('pwd');
        Log::info(Hash::make('password'));

        $contactDetails = ContactGroup::where('user_id', $request->userId)->where('id', $request->groupId)->first();
        return response()->json([
            'status' => 200,
            'data' => $contactDetails,
            'message' => 'success'
        ]); 

    }

    public function deleteGroup(Request $request){
        Log::info('in delete');
        Log::info($request);
        $id = $request->id;
        $group = ContactGroup::where('id', $id);
        $group->update('soft_delete', 1);
        $theGroup = $group->first();
        $name = $theGroup->name;
        $group->delete();
        return response()->json([
            'status' => 200,
            'data' => 'Group '.$name.' was deleted',
            'message' => 'success'
        ]); 
    }
    
}
