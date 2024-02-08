<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Enduser;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\BillsMiddleware;
use App\Http\Controllers\PaymentController;

class UssdBillsController extends Controller
{
    //
    public function purchaseAirtime(Request $request)
    {
	 Log::info('here');
        $validator = Validator::make($request->all(), [
            'phone'  => "required",
            'network'  => "required",
            'amount'  => "required|integer|min:1",
            //'product'  => "required",
            'vend_type' => "required",
            'pin'  => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'required_fields' => $validator->errors()->all(),
                'message' => 'Missing field(s)',
                'status' => '500'
            ]);
        }
        try {

            $enduserR = Enduser::find($request->user_id);
            $msisdn = $enduserR->mobilePhone;
            Log::info($request);
            $phone = $request->phone;
            $network = $request->network;
            $amount = $request->amount;
            $vend_type = $request->vend_type;
            $product = $network . "_AIRTIME";
            $pin = $request->pin;

            $conn = env('APP_URL');
            $theBody = array(
                'mobilePhone' => $msisdn,
                'product' => $product,
            );

            $discount = new PaymentController;

            $thePayload = new Request($theBody);

            $response = $discount->getDiscount($thePayload);
            Log::info("getDiscount-response" . $response->getContent());
            $ress = json_decode($response->getContent());

            if ($ress->status == 200) {
                Log::info('in here');		    
                $discount = $ress->data;
                
                Log::info(gettype($discount));
                $discountedAmt = $amount - ($amount * $discount / 100);
                Log::info('in here 3');
                $desc = "Payment of Airtime";
                $requestid = time() . rand(100, 999);
                $requestId = $requestid . "Airtime";


                $body = array(
                    'mobilePhone' => $msisdn,
                    'amount' => $discountedAmt,
                    'product' => $product,
                    'description' => $desc,
                    'pin' => $pin,
                    'request_id' => $requestId
                );
                $payload = new Request($body);

                $payment = new PaymentController;
                $response = $payment->payment($payload);

                Log::info("payment-response" . $response->getContent());
                $resp = json_decode($response->getContent());

                if ($resp->status == 200) {

                    DB::table('airtime_requests')->insert([
                        "user_id" => $msisdn,
                        "network" => $network,
                        "amount" => $discountedAmt,
                        "status" => "0",
                        "type" => $vend_type,
                        "request_id" => $requestId,
                        "phone_number" => $phone,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);

                    $body = array(
                        "phone" => $phone,
                        "amount" => $amount,
                        "vend_type" => $vend_type,
                        "network" => $network,
                        "request_id" => $requestId,
                    );
                    Log::info($body);
                    Log::info('body above');
                    $req = new Request($body);
                    $tr = new BillsMiddleware;
                    $reqs = $tr->airTimeVending($req);
                    Log::info($reqs);
                    $msg = json_decode($reqs);
                    if (isset($msg)) {
                        if ($msg->status == "200") {
                            /* code for beneficiary */
                            Log::info('inside');
                            if ($request->beneficiary == '1') {
                                $body = array(
                                    "msisdn" => $msisdn,
                                    "receiver" => $phone,
                                    "network" => $network,
                                    "product" => "airtime",
                                    "name" => $request->name,
                                );
                                $req = new Request($body);
                                $ben = new BeneficiaryController;
                                $tr = $ben->createBeneficiary($req);
                            }
                            /* code for beneficiary */
                            DB::table('airtime_requests')->where('request_id', $requestId)->update(['status' => "1", "trans_code" => $msg->transId]);
                            return response()->json([
                                'message' => "Success",
                                'status' => '200',
                                'data' => $msg,
                                'amountCharged' => number_format($discountedAmt, 2),
                                'discount' => $discount . "%"
                            ]);
                        } else {
                            DB::table('airtime_requests')->where('request_id', $requestId)->update(['status' => "2"]);
                            
                            $rvBody = array(
                                'reference' => $requestId,
                            );

                            $RvPayload = new Request($rvBody);
            
                            $reversal = new PaymentController;

                            $response = $reversal->reversal($RvPayload);
            
                            Log::info("Reversal-response" . $response->getContent());
                            $rev = json_decode($response->getContent());

                            if ($rev->status == 200) {
                                DB::table('airtime_requests')->where('request_id', $requestId)->update(['reversal_status' => "1"]);
                                return response()->json([
                                    'message' => "Purchase Failed...Reversal successful",
                                    'status' => '300'
                                ]);
                            } else {
                                return response()->json([
                                    'message' => "Failed..",
                                    'status' => '300'
                                ]);
                            }
                        }
                    } else {
                        return response()->json([
                            'message' => "Failed",
                            'status' => '300'
                        ]);
                    }
                } elseif ($resp->status == 300)  {
                    DB::table('airtime_requests')->insert([
                        "user_id" => $msisdn,
                        "network" => $network,
                        "amount" => $discountedAmt,
                        "status" => "2",
                        "type" => $vend_type,
                        "request_id" => $requestId,
                        "phone_number" => $phone,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                    return response()->json([
                        'message' => $resp->message,
                        'status' => '300'
                    ]);
                }else{
                    DB::table('airtime_requests')->insert([
                        "user_id" => $msisdn,
                        "network" => $network,
                        "amount" => $discountedAmt,
                        "status" => "0",
                        "type" => $vend_type,
                        "request_id" => $requestId,
                        "phone_number" => $phone,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                    return response()->json([
                        'message' => 'pending',
                        'status' => '500'
                    ]);
                }
            } else {
                return response()->json([
                    'message' => "An error occured. Please try again later",
                    'status' => '400'
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

    public function lookUpData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            //'phone'  => "required",
            'network'  => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'required_fields' => $validator->errors()->all(),
                'message' => 'Missing field(s)',
                'status' => '500'
            ]);
        }
        try {
            $enduserR = Enduser::find($request->user_id);
            $msisdn = $enduserR->mobilePhone;
            Log::info($request);
            $phone = $request->phone;
            $network = $request->network;

            $body = array(
                "phone" => $phone,
                "network" => $network,
            );
            $req = new Request($body);
            $tr = new BillsMiddleware;
            $reqs = $tr->dataLookUp($req);
            //dd($reqs);
            $msg = json_decode($reqs);
            Log::info("responsestatus - " . $msg->status); //dd($msg);
            if (isset($msg)) {
                if ($msg->status == "200") {
                    return response()->json([
                        'message' => "Success",
                        'status' => '200',
                        'data' => $msg->product
                    ]);
                } else {
                    return response()->json([
                        'message' => "Failed.",
                        'status' => '300'
                    ]);
                }
            } else {
                return response()->json([
                    'message' => "Failed..",
                    'status' => '300'
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

    public function purchaseData(Request $request)
    {
	    Log::info('here'); 
	    $validator = Validator::make($request->all(), [
            'phone'  => "required",
            'bundle'  => "required",
            'network'  => "required",
            'amount'  => "required|integer|min:1",
            'product'  => "required",
            'package' => "required",
            'pin'  => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'required_fields' => $validator->errors()->all(),
                'message' => 'Missing field(s)',
                'status' => '500'
            ]);
        }
        try {

            $enduserR = Enduser::find($request->user_id);
            $msisdn = $enduserR->mobilePhone;
            Log::info($request);
            $phone = $request->phone;
            $bundle = $request->bundle;
            $network = $request->network;
            $amount = $request->amount;
            $product = $request->product;
            $package = $request->package;
            $pin = $request->pin;

            $conn = env('APP_URL');
            $theBody = array(
                'mobilePhone' => $msisdn,
                'product' => $product,
            );

            $discount = new PaymentController;

            $thePayload = new Request($theBody);

            $response = $discount->getDiscount($thePayload);
            Log::info("getDiscount-response" . $response->getContent());
            $ress = json_decode($response->getContent());

            if ($ress->status == 200) {
                $discount = $ress->data;
                $discountedAmt = $amount - ($amount * $discount / 100);
                $desc = "Payment of Data";
                $requestid = time() . rand(100, 999);
                $requestId = $requestid . "Data";

                $body = array(
                    'mobilePhone' => $msisdn,
                    'amount' => $discountedAmt,
                    'product' => $product,
                    'description' => $desc,
                    'pin' => $pin,
                    'request_id' => $requestId,
                );

               $payload = new Request($body);

                $payment = new PaymentController;
                $response = $payment->payment($payload);

                Log::info("payment-response" . $response->getContent());
                $resp = json_decode($response->getContent());
                if ($resp->status == 200) {

                    DB::table('data_requests')->insert([
                        "user_id" => $msisdn,
                        "network" => $network,
                        "amount" => $discountedAmt,
                        "status" => "0",
                        "request_id" => $requestId,
                        "phone_number" => $phone,
                        "package" => $package,
                        //"response" => $resp->message,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);

                    $body = array(
                        "phone" => $phone,
                        "amount" => (int)$amount,
                        "bundle" => $bundle,
                        "network" => $network,
                        "package" => $package,
                        "request_id" => $requestId
                    );
                    $req = new Request($body);
                    $tr = new BillsMiddleware;
                    $req = $tr->dataVending($req);
                    Log::info('response - ' . $req);
                    $msg = json_decode($req);
                    if (isset($msg)) {
                        if ($msg->status == "200") {
                            /* code for beneficiary */
                            if ($request->beneficiary == '1') {
                                $body = array(
                                    "msisdn" => $msisdn,
                                    "receiver" => $phone,
                                    "network" => $network,
                                    "product" => "data",
                                    "name" => $request->name,
                                );
                                $req = new Request($body);
                                $ben = new BeneficiaryController;
                                $tr = $ben->createBeneficiary($req);
                            }
                            /* code for beneficiary */
                            DB::table('data_requests')->where('request_id', $requestId)->update(['status' => "1", 'trans_code' => $msg->transId]);
                            return response()->json([
                                'message' => "Success",
                                'status' => '200',
                                'data' => $msg,
                                'amountCharged' => number_format($discountedAmt, 2),
                                'discount' => $discount . "%"
                            ]);
                        } else{
                            DB::table('data_requests')->where('request_id', $requestId)->update(['status' => "2"]);
                            $rvBody = array(
                                'reference' => $requestId,
                            );

                            $RvPayload = new Request($rvBody);
            
                            $reversal = new PaymentController;

                            $response = $reversal->reversal($RvPayload);
            
                            Log::info("Reversal-response" . $response->getContent());
                            $rev = json_decode($response->getContent());

                            if ($rev->status == 200) {
                                DB::table('data_requests')->where('request_id', $requestId)->update(['reversal_status' => "1"]);
                                return response()->json([
                                    'message' => "Purchase Failed...Reversal successful",
                                    'status' => '300'
                                ]);
                            } else {
                                return response()->json([
                                    'message' => "Failed..",
                                    'status' => '300'
                                ]);
                            }
                        }
                    } else {
                        return response()->json([
                            'message' => "Failed",
                            'status' => '300'
                        ]);
                    }
                } elseif ($resp->status == 300)  {
                    DB::table('data_requests')->insert([
                        "user_id" => $msisdn,
                        "network" => $network,
                        "amount" => $discountedAmt,
                        "status" => "2",
                        "request_id" => $requestId,
                        "phone_number" => $phone,
                        "response" => $resp->message,
                        "package" => $package,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                    return response()->json([
                        'message' => $resp->message,
                        'status' => '300'
                    ]);
                }else{
                    DB::table('data_requests')->insert([
                        "user_id" => $msisdn,
                        "network" => $network,
                        "amount" => $discountedAmt,
                        "status" => "0",
                        "request_id" => $requestId,
                        "phone_number" => $phone,
                        "response" => $resp->message,
                        "package" => $package,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                    return response()->json([
                        'message' => $resp->message,
                        'status' => '500'
                    ]);
                }
            } else {
                return response()->json([
                    'message' => "An error occured. Please try again later",
                    'status' => '400'
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

    public function discoValidation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'disco'  => "required",
            'meterNo'  => "required",
            'type' => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                'required_fields' => $validator->errors()->all(),
                'message' => 'Missing field(s)',
                'status' => '500'
            ]);
        }
        try {
            $enduserR = Enduser::find($request->user_id);
            $msisdn = $enduserR->mobilePhone;
            Log::info($request);
            $disco = $request->disco;
            $meterNo = $request->meterNo;
            $type = $request->type;

            $body = array(
                "disco" => $disco,
                "type" => $type,
                "meterNo" => $meterNo,
            );
            $req = new Request($body);
            if ($type == "POSTPAID") {
                $tr = new BillsMiddleware;
                $reqs = $tr->postPaidValidation($req);
                Log::info($reqs);
            } else {
                $tr = new BillsMiddleware;
                $reqs = $tr->prePaidValidation($req);
                Log::info($reqs);
            }
            $msg = json_decode($reqs);
            //$msg = (object) $msg;
            if (isset($msg)) {
                if ($msg->status == "200") {
                    return response()->json([
                        'message' => "Success",
                        'status' => '200',
                        'data' => $msg,
                    ]);
                } else {
                    return response()->json([
                        'message' => "Failed!",
                        'status' => '300'
                    ]);
                }
            } else {
                return response()->json([
                    'message' => "Failed!.",
                    'status' => '300'
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

    public function purchaseDisco(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'disco'  => "required",
            'meterNo'  => "required",
            'type' => "required",
            'amount' => "required|integer|min:1",
            'phonenumber' => "required",
            'name' => "required",
            'address' => "required",
            'pin'  => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'required_fields' => $validator->errors()->all(),
                'message' => 'Missing field(s)',
                'status' => '500'
            ]);
        }
        try {
            $pin = $request->pin;

            $enduserR = Enduser::find($request->user_id);
            $msisdn = $enduserR->mobilePhone;
            Log::info($request);
            $phone = $request->phonenumber;
            $disco = $request->disco;
            $meterNo = $request->meterNo;
            $type = $request->type;
            $amount = $request->amount;
            $name = $request->name;
            $address = $request->address;
            $product = $disco . "_" . $type;

            $conn = env('APP_URL');

            $theBody = array(
                'mobilePhone' => $msisdn,
                'product' => $product,
            );

            $discount = new PaymentController;

            $thePayload = new Request($theBody);

            $response = $discount->getDiscount($thePayload);
            Log::info("getDiscount-response" . $response->getContent());
            $ress = json_decode($response->getContent());


            if ($ress->status == 200) {
                $discount = $ress->data;
                $discountedAmt = $amount - ($amount * $discount / 100);
                $desc = "Payment of Disco";
                $requestid = time() . rand(100, 999);
                $requestId = $requestid . "Disco";

                
                $body = array(
                    'mobilePhone' => $msisdn,
                    'amount' => $discountedAmt,
                    'product' => $product,
                    'description' => $desc,
                    'pin' => $pin,
                    'request_id' => $requestId,
                );
                $payload = new Request($body);

                $payment = new PaymentController;
                $response = $payment->payment($payload);

                Log::info("payment-response" . $response->getContent());
                $resp = json_decode($response->getContent());

                if ($resp->status == 200) {

                    DB::table('disco_requests')->insert([
                        "user_id" => $msisdn,
                        "meterNo" => $meterNo,
                        "phoneNumber" => $phone,
                        "amount" => $discountedAmt,
                        "type" => $type,
                        "disco" => $disco,
                        "status" => "0",
                        "name" => $name,
                        "address" => $address,
                        "request_id" => $requestId,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);

                    $body = array(
                        "phonenumber" => $phone,
                        "disco" => $disco,
                        "meterNo" => $meterNo,
                        "type" => $type,
                        "amount" => $amount,
                        "name" => $name,
                        "address" => $address,
                        "request_id" => $requestId
                    );
                    Log::info($body);
                    $req = new Request($body);
                    $tr = new BillsMiddleware;
                    if ($type == "POSTPAID") {
                        $reqs = $tr->postPaidDiscoBuy($req);
                        Log::info($reqs);
                    } else {
                        $reqs = $tr->prePaidDiscoBuy($req);
                        Log::info($reqs);
                    }
                    $msg = json_decode($reqs);
                    if (isset($msg)) {
                        if ($msg->status == "200") {
                            if (stripos($msg->disco, 'PREPAID') !== false) {
                                DB::table('disco_requests')->where('request_id', $requestId)->update(['status' => "1", "token" => $msg->token, "units" => $msg->unit, "trans_code" => $msg->TransRef, "tax" => $msg->taxAmount, "configureToken" => $msg->configureToken, "resetToken" => $msg->resetToken]);
                            }else{
                                DB::table('disco_requests')->where('request_id', $requestId)->update(['status' => "1","units" => $msg->unit, "trans_code" => $msg->TransRef,"tax" => $msg->taxAmount,]);
                            }
                            return response()->json([
                                'message' => "Success",
                                'status' => '200',
                                'token' => isset($msg->token) ? $msg->token : NULL,
                                'unit' => $msg->unit,
                                'data' => $msg,
                                'amountCharged' => number_format($discountedAmt, 2),
                                'discount' => $discount . "%"
                            ]);
                        } else {
                            DB::table('disco_requests')->where('request_id', $requestId)->update(['status' => "2"]);
                            $rvBody = array(
                                'reference' => $requestId,
                            );

                            $RvPayload = new Request($rvBody);
            
                            $reversal = new PaymentController;

                            $response = $reversal->reversal($RvPayload);
            
                            Log::info("Reversal-response" . $response->getContent());
                            $rev = json_decode($response->getContent());

                            if ($rev->status == 200) {
                                DB::table('disco_requests')->where('request_id', $requestId)->update(['reversal_status' => "1"]);
                                return response()->json([
                                    'message' => "Purchase Failed...Reversal successful",
                                    'status' => '300'
                                ]);
                            } else {
                                return response()->json([
                                    'message' => $msg->message,
                                    'status' => '300'
                                ]);
                            }
                        }
                    } else {
                        return response()->json([
                            'message' => "Failed.",
                            'status' => '300'
                        ]);
                    }
                } elseif ($resp->status == 300)  {
                    DB::table('disco_requests')->insert([
                        "user_id" => $msisdn,
                        "meterNo" => $meterNo,
                        "phoneNumber" => $phone,
                        "amount" => $discountedAmt,
                        "type" => $type,
                        "status" => "2",
                        "response" => $resp->message,
                        "name" => $name,
                        "address" => $address,
                        "request_id" => $requestId,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                    return response()->json([
                        'message' => $resp->message,
                        'status' => '300'
                    ]);
                }else{
                    DB::table('disco_requests')->insert([
                        "user_id" => $msisdn,
                        "meterNo" => $meterNo,
                        "phoneNumber" => $phone,
                        "amount" => $discountedAmt,
                        "type" => $type,
                        "status" => "0",
                        "response" => $resp->message,
                        "name" => $name,
                        "address" => $address,
                        "request_id" => $requestId,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                    return response()->json([
                        'message' => 'Pending',
                        'status' => '500'
                    ]);
                }
            } else {
                return response()->json([
                    'message' => isset($ress->message) ? $ress->message : 'Debit Failed',
                    'status' => '400'
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

    public function tvPurchase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customerName'  => "required",
            'smartCardNo'  => "required",
            'type' => "required",
            'amount' => "required|integer|min:1",
            'packagename' => "required",
            'productsCode' => "required",
            'period' => "required",
            'hasAddon' => "required",
            'pin'  => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'required_fields' => $validator->errors()->all(),
                'message' => 'Missing field(s)',
                'status' => '500'
            ]);
        }
        try {
            
            $pin = $request->pin;

            $enduserR = Enduser::find($request->user_id);
            $msisdn = $enduserR->mobilePhone;
            Log::info($request);

            $customerName = $request->customerName;
            $smartCardNo = $request->smartCardNo;
            $type = $request->type;
            $amount = $request->amount;
            $packagename = $request->packagename;
            $productsCode = $request->productsCode;
            $period = $request->period;
            $hasAddon = $request->hasAddon;
            $product = $type . "_ACCESS";

            if($hasAddon == "0"){
                $theBody = array(
                    'mobilePhone' => $msisdn,
                    'product' => $product,
                );
                $discount = new PaymentController;
                $thePayload = new Request($theBody);

                $response = $discount->getDiscount($thePayload);
                Log::info("getDiscount-response" . $response->getContent());
                $ress = json_decode($response->getContent());

                if ($ress->status == 200) {
                    $discount = $ress->data;
                    $discountedAmt = $amount - ($amount * $discount / 100);
                    $desc = "Payment of TV";
                    $requestid = time() . rand(100, 999);
                    $requestId = $requestid . "TV";
    
                    $body = array(
                        'mobilePhone' => $msisdn,
                        'amount' => $discountedAmt,
                        'product' => $product,
                        'description' => $desc,
                        'pin' => $pin,
                        'request_id' => $requestId
                    );
    
                    $payload = new Request($body);
    
                    $payment = new PaymentController;
                    $response = $payment->payment($payload);
    
                    Log::info("payment-response" . $response->getContent());
                    $resp = json_decode($response->getContent());
    
                    if ($resp->status == 200) {
                        DB::table('paytv_requests')->insert([
                            "user_id" => $msisdn,
                            "smartcardNumber" => $smartCardNo,
                            "customerName" => $customerName,
                            "type" => $type,
                            "amount" => $discountedAmt,
                            "status" => "0",
                            "request_id" => $requestId,
                            "period" => $period,
                            "addondetails" => $hasAddon,
                            "productsCodes" => $productsCode,
                            "created_at" => now(),
                            "updated_at" => now(),
                        ]);
    
                        $body = array(
                            "smartCardNo" => $smartCardNo,
                            "customerName" => $customerName,
                            "type" => $type,
                            "amount" => $amount,
                            "packagename" => $packagename,
                            "productsCode" => $productsCode,
                            "period" => $period,
                            "hasAddon" => $hasAddon,
                            "request_id" => $requestId
                        );
    
                        $req = new Request($body);
                        $tr = new BillsMiddleware;
                        $req = $tr->purchaseTv($req);
                        Log::info($req);
                        $msg = json_decode($req);
                        //mr isaac check
                        if (isset($msg)) {
                            if ($msg->status == "200") {
                                DB::table('paytv_requests')->where('request_id', $requestId)->update(['status' => "1","trans_code" => $msg->transId]);
                                return response()->json([
                                    'message' => "Success",
                                    'status' => '200',
                                    'data' => $msg,
                                    'amountCharged' => number_format($discountedAmt, 2),
                                    'discount' => $discount . "%"
                                ]);
                            } else {
                                DB::table('paytv_requests')->where('request_id', $requestId)->update(['status' => "2"]);
                                $rvBody = array(
                                    'reference' => $requestId,
                                );
    
                                $RvPayload = new Request($rvBody);
                
                                $reversal = new PaymentController;
    
                                $response = $reversal->reversal($RvPayload);
                
                                Log::info("Reversal-response" . $response->getContent());
                                $rev = json_decode($response->getContent());
    
                                if ($rev->status == 200) {
                                    DB::table('paytv_requests')->where('request_id', $requestId)->update(['reversal_status' => "1"]);
                                    return response()->json([
                                        'message' => "Purchase Failed...Reversal successful",
                                        'status' => '300'
                                    ]);
                                } else {
                                    return response()->json([
                                        'message' => "Failed..",
                                        'status' => '300'
                                    ]);
                                }
                            }
                        } else {
                            return response()->json([
                                'message' => "Failed",
                                'status' => '300'
                            ]);
                        }
                    } elseif($resp->status == 300) {
                        DB::table('paytv_requests')->insert([
                            "user_id" => $msisdn,
                            "smartcardNumber" => $smartCardNo,
                            "customerName" => $customerName,
                            "type" => $type,
                            "amount" => $discountedAmt,
                            "status" => "2",
                            "request_id" => $requestId,
                            "period" => $period,
                            "addondetails" => $hasAddon,
                            "productsCodes" => $productsCode,
                            "response" => $resp->message,
                            "created_at" => now(),
                            "updated_at" => now(),
                        ]);
                        return response()->json([
                            'message' => $resp->message,
                            'status' => '300'
                        ]);
                    }else{
                        DB::table('paytv_requests')->insert([
                            "user_id" => $msisdn,
                            "smartcardNumber" => $smartCardNo,
                            "customerName" => $customerName,
                            "type" => $type,
                            "amount" => $discountedAmt,
                            "status" => "0",
                            "request_id" => $requestId,
                            "period" => $period,
                            "addondetails" => $hasAddon,
                            "productsCodes" => $productsCode,
                            "response" => $resp->message,
                            "created_at" => now(),
                            "updated_at" => now(),
                        ]);
                        return response()->json([
                            'message' => 'Pending',
                            'status' => '500'
                        ]);
                    }
                } else {
                    return response()->json([
                        'message' => "An error occured. Please try again later",
                        'status' => '400'
                    ]);
                }
            }else{
                $validator = Validator::make($request->all(), [
                    'addonproductCode'  => "required",
                    'addonAmount'  => "required",
                    'addonproductName' => "required",
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'required_fields' => $validator->errors()->all(),
                        'message' => 'Missing field(s)',
                        'status' => '500'
                    ]);
		}
		Log::info('inside addons');
                $addonproductCode = $request->addonproductCode;
                $addonAmount = $request->addonAmount;
                $addonproductName = $request->addonproductName;

                $amtAddOn = $amount + $addonAmount;

                $theBody = array(
                    'mobilePhone' => $msisdn,
                    'product' => $product,
                );
                $discount = new PaymentController;
                $thePayload = new Request($theBody);

                $response = $discount->getDiscount($thePayload);
                Log::info("getDiscount-response" . $response->getContent());
                $ress = json_decode($response->getContent());

                if ($ress->status == 200){
                    $discount = $ress->data;
                    $discountedAmt = $amtAddOn - ($amtAddOn * $discount / 100);
                    $desc = "Payment of TV";
                    $requestid = time() . rand(100, 999);
                    $requestId = $requestid . "TV";

                    $body = array(
                        'mobilePhone' => $msisdn,
                        'amount' => $discountedAmt,
                        'product' => $product,
                        'description' => $desc,
                        'pin' => $pin,
                        'request_id' => $requestId
                    );
    
                    $payload = new Request($body);
    
                    $payment = new PaymentController;
                    $response = $payment->payment($payload);
    
                    Log::info("payment-response" . $response->getContent());
                    $resp = json_decode($response->getContent());

                    if ($resp->status == 200) {
                        DB::table('paytv_requests')->insert([
                            "user_id" => $msisdn,
                            "smartcardNumber" => $smartCardNo,
                            "customerName" => $customerName,
                            "type" => $type,
                            "amount" => $discountedAmt,
                            "status" => "0",
                            "request_id" => $requestId,
                            "period" => $period,
                            "addondetails" => $addonproductName .'|'. $addonproductCode .'|'. $addonAmount,
                            "productsCodes" => $productsCode .'|'. $amount,
                            "created_at" => now(),
                            "updated_at" => now(),
                        ]);

                        $body = array(
                            "smartCardNo" => $smartCardNo,
                            "customerName" => $customerName,
                            "type" => $type,
                            "amount" => $amount,
                            "packagename" => $packagename,
                            "productsCode" => $productsCode,
                            "period" => $period,
                            "hasAddon" => $hasAddon,
                            "request_id" => $requestId,
                            "addonproductCode" => $addonproductCode,
                            "addonproductName" => $addonproductName,   
                            "addonAmount" => $addonAmount,
                        );

                        $req = new Request($body);
                        $tr = new BillsMiddleware;
                        $req = $tr->dstvPlusAddonPurchase($req);
                        Log::info($req);
                        $msg = json_decode($req);
                        //mr isaac check
                        if (isset($msg)) {
                            if ($msg->status == "200") {
                                DB::table('paytv_requests')->where('request_id', $requestId)->update(['status' => "1","trans_code" => $msg->transId]);
                                return response()->json([
                                    'message' => "Success",
                                    'status' => '200',
                                    'data' => $msg,
                                    'amountCharged' => number_format($discountedAmt, 2),
                                    'discount' => $discount . "%"
                                ]);
                            } elseif($msg->status == "300") {
                                DB::table('paytv_requests')->where('request_id', $requestId)->update(['status' => "2"]);
                                $rvBody = array(
                                    'reference' => $requestId,
                                );

                                $RvPayload = new Request($rvBody);

                                $reversal = new PaymentController;

                                $response = $reversal->reversal($RvPayload);

                                Log::info("Reversal-response" . $response->getContent());
                                $rev = json_decode($response->getContent());

                                if ($rev->status == 200) {
                                    DB::table('paytv_requests')->where('request_id', $requestId)->update(['reversal_status' => "1"]);
                                    return response()->json([
                                        'message' => "Purchase Failed...Reversal successful",
                                        'status' => '300'
                                    ]);
                                } else {
                                    return response()->json([
                                        'message' => "Failed..",
                                        'status' => '300'
                                    ]);
                                }
                            }else{
                                return response()->json([
                                    'message' => "Pending..",
                                    'status' => '400'
                                ]);
                            }
                        } else {
                            return response()->json([
                                'message' => "Failed",
                                'status' => '300'
                            ]);
                        }
                    } elseif($resp->status == 300) {
                        DB::table('paytv_requests')->insert([
                            "user_id" => $msisdn,
                            "smartcardNumber" => $smartCardNo,
                            "customerName" => $customerName,
                            "type" => $type,
                            "amount" => $discountedAmt,
                            "status" => "2",
                            "request_id" => $requestId,
                            "period" => $period,
                            "addondetails" => $addonproductName .'|'. $addonproductCode .'|'. $addonAmount,
                            "productsCodes" => $productsCode .'|'. $amount,
                            "response" => $resp->message,
                            "created_at" => now(),
                            "updated_at" => now(),
                        ]);
                        return response()->json([
                            'message' => $resp->message,
                            'status' => '300'
                        ]);
                    }else{
                        DB::table('paytv_requests')->insert([
                            "user_id" => $msisdn,
                            "smartcardNumber" => $smartCardNo,
                            "customerName" => $customerName,
                            "type" => $type,
                            "amount" => $discountedAmt,
                            "status" => "0",
                            "request_id" => $requestId,
                            "period" => $period,
                            "addondetails" => $addonproductName .'|'. $addonproductCode .'|'. $addonAmount,
                            "productsCodes" => $productsCode .'|'. $amount,
                            "response" => $resp->message,
                            "created_at" => now(),
                            "updated_at" => now(),
                        ]);
                        return response()->json([
                            'message' => 'Pending',
                            'status' => '500'
                        ]);
                    }
                }else {
                    return response()->json([
                        'message' => "An error occured. Please try again later",
                        'status' => '400'
                    ]);
                }
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
