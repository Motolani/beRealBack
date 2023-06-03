<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Traits\HttpResponses;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthActivities
{
    use HttpResponses;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {   
        Log::info($request);
        $userToken = $request->header('userToken');
        Log::info($userToken);
        $userId = Auth::id();
        $check = DB::table('personal_access_tokens')->where('tokenable_id', $userId)->where('token', $userToken);
        if($check->exists()){
            return $next($request);
        }else{
            return $this->unauthorized('', 'Invalid User', 401);
        }
    }
}
