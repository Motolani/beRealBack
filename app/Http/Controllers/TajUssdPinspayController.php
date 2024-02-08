<?php

namespace App\Http\Controllers;

use App\Enduser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Http\Controllers\Helpers\CurlClass;
use App\VerifyToken;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Storage;

class TajUssdPinspayController extends Controller
{
    //
    public function tajUssdRegistration(Request $request)
    {
        Log::info($request);
        $validator = Validator::make($request->all(), [
            'mobilePhone'  => ['required', 'string', 'max:200'],
            'pin' => ['required', 'string', 'max:4'],
            'dob' => ['required', 'string', 'max:255'],
            'email'  => ['required', 'string', 'max:200'],
            'firstName'  => ['required', 'string', 'max:255'],
            'lastName'  => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'required_fields' => $validator->errors()->all(),
                'message' => 'Missing field(s) | Validation Error',
                'status' => '500'
            ]);
        }
        try {
            Log::info($request);
            $mobilePhone = $request->mobilePhone;
            $convert = substr($mobilePhone, -10);
            $msisdn = "234" . $convert;
            $firstName = $request->firstName;
            $lastName = $request->lastName;
            $gender = $request->gender;
            $lang = $request->language;
            $bvn = $request->bvn;
            $firstTwo = substr($request->dob, 0, -6);
            $secondTwo = substr($request->dob, 2, -4);
            $lastTwo = substr($request->dob, 4);
            $dob = $firstTwo .'/'.$secondTwo.'/'.$lastTwo;
            $language = $request->language;

            $enduser = \App\Enduser::where('mobilePhone', $msisdn);
            if (!$enduser->exists()) {
                $pnd = "1";
                $endUser = new \App\Enduser([
                    "requestId" => time() . rand(100, 999),
                    "mobilePhone" => $msisdn,
                    "firstName" => $firstName,
                    "lastName" => $lastName,
                    "gender" => $gender,
                    "language" => $lang,
                    "enterpriceId" => $pnd,
                    "status" => "0",
                    "pwa_status" => "1",
                    "email" => $request->email,
                    "bvn" => $bvn,
                    "dob" => $dob,
                ]);
                $endUser->save();
                $security = DB::table('securities')->where('name', 'm_token')->first();
                $body = http_build_query([
                    "RequestID" => time() . rand(100, 999),
                    "MobilePhone" => $msisdn,
                    "Firstname" => $firstName,
                    "Lastname" => $lastName,
                    "Gender" => $gender,
                    "DoB" => $dob,
                    "Pin" => $request->pin,
                    "Language" => $language,
                    "BVN" => $request->bvn ? $request->bvn : "234567891",
                    "m_token" => $security->token,
                ]);

                
                $conn = config('tajbank.tajbank_conn');
                $url = $conn . "Pmoney/Enrollment.php";
                $response = CurlClass::curlApi($body, $url, "POST");
                Log::info($response);
                $dec = json_decode($response);

                if (isset($dec)) {
                    if (isset($dec->ResponseCode)) {
                        if ($dec->ResponseCode == '00') {
                            \App\Enduser::where('mobilePhone', $msisdn)->update(["status" => "1", "taj_reg_status" => 1]);
                            return response()->json([
                                "status" => "200",
                                "message" => $dec->ResponseMessage,
                            ]);
                        } elseif ($dec->ResponseCode == "700") {
                            \App\Enduser::where('mobilePhone', $msisdn)->update(["taj_reg_status" => 2]);
                            return response()->json([
                                'message' => $dec->message,
                                'status' => $dec->ResponseCode,
                            ]);
                        } else {
                            \App\Enduser::where('mobilePhone', $msisdn)->update(["taj_reg_status" => 2]);
                            return response()->json([
                                "status" => "300",
                                "message" => "registration Failed.",
                            ]);
                        }
                    } else {
                        \App\Enduser::where('mobilePhone', $msisdn)->update(["taj_reg_status" => 2]);
                        return response()->json([
                            "status" => "300",
                            "message" => "registration Failed.",
                        ]);
                    }
                } else {
                    \App\Enduser::where('mobilePhone', $msisdn)->update(["taj_reg_status" => 2]);
                    return response()->json([
                        "status" => "300",
                        "message" => "registration Failed.",
                    ]);
                }
            } else {
                return response()->json([
                    "status" => "300",
                    "message" => "user already exists"
                ]);
            }

        } catch (\Throwable $ex) {
            Log::debug($ex->getMessage());
            return response()->json([
                'message' => "An error occured. Please try again later",
                'status' => '400'
            ]);
        } catch (Exception $e) {
            Log::debug($e->getMessage());
            return response()->json([
                'message' => "An error occured. Please try again later",
                'status' => '400'
            ]);
        }
    }
}
