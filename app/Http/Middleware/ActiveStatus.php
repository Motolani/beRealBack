<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->apiKey){
            $apiKey = $request->apiKey;  
        }elseif($request->header('apiKey')){
            $apiKey = $request->header('apiKey');
        }else{
            return response()->json([
                'message' => "Failed!",
                'status' => '300',
            ]);
        }

        $enterpriseUser = User::where("liveKey", $apiKey);
        if($enterpriseUser->exists()){
            $ent = $enterpriseUser->first();
            if($ent->active_status == 1){
                return $next($request);
            }else{
                return response()->json([
                    'message' => "User Account Blocked",
                    'status' => '300',
                ]);
            }
        }else{
            return response()->json([
                'message' => "Failed!",
                'status' => '300',
            ]);
        }

    }
}
