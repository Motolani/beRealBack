<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    use HttpResponses;

    public function login(LoginUserRequest $request)
    {

        Log::info($request);
        $request->validated($request->all());
        $credentials = [
            'email' => $request['email'],
            'password' => $request['password'],
        ];

        if(!Auth::attempt($credentials)){
            return $this->error('', 'Credentials do not match', 401);
        }

        $user = User::where('email', $request->email)->first();
        

        return $this->sucess([
            'user' => $user,

            'token' => $user->createToken('User Api Token of ' . $user->name)->plainTextToken
        ]);
    }

    public function Register(RegisterUserRequest $request)
    {
        $request->validated($request->all());

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        return $this->sucess([
            'user' => $user,
            'token' => $user->createToken('User Api Token of ' . $user->name)->plainTextToken
        ]);
    }
}
