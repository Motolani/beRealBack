<?php

private function pinspay1(Request $request)
    {
        $set = Ussd_session::where('session', $request->sessionid)->where('msisdn', $request->msisdn)->first();
        Log::info($set);

        $set->screen = 1;
        $set->level = 12;
        $set->save();

        $val = $this->pinspayValdiate($request->msisdn);
        Log::info(serialize('validation == '.$val));
        $dec = json_decode($val);

        if($dec->status == "200"){
            $msg = "Welcome, ...bla bla bla, enter amount";
            return response()->json([
                'data' =>$msg,
                'endofsession' => 1
            ]);
        }elseif($dec->status == "500"){
            $msg = "who you be?";
            return response()->json([
                'data' =>$msg,
                'endofsession' => 2
            ]);
        }else{
            $msg = "You are not a Pinspay User, Please register to continue";
            return response()->json([
                'data' => $msg,
                'endofsession' => 2
            ]);
        }
    }

    private function pinspay2(Request $request)
    {
        $set = Ussd_session::where('session', $request->sessionid)->where('msisdn', $request->msisdn)->first();
        Log::info($set);

        $set->screen = 2;
        $set->level = 12;
        $set->save();

        $amount = $request->input;
        //there is a validate endpoint to check if user has the said money in his acct
        //you need to call it to validate the amount
        $chk = $this->validateAmt($amount);
        Log::info(serialize('check amt == '.$chk));
        $dec = json_decode($chk);

        if($dec->status == "200"){
            $set->amount = $amount;
            $set->save();

            $msg = "please enter pin";
            return response()->json([
                'data' =>$msg,
                'endofsession' => 1
            ]);
        }elseif($dec->status == "500"){
            $msg = "who you be?";
            return response()->json([
                'data' =>$msg,
                'endofsession' => 2
            ]);
        }else{
            $msg = "You dont have sufficient money to complete this transaction";
            return response()->json([
                'data' => $msg,
                'endofsession' => 2
            ]);
        }

    }


    private function pinspay3(Request $request)
    {
        $set = Ussd_session::where('session', $request->sessionid)->where('msisdn', $request->msisdn)->first();
        Log::info($set);

        $set->screen = 3;
        $set->level = 12;
        $set->pin = $request->pin;
        $set->save();

        $msg = "Payment Summary:\nAmount: " . $set->amount . "\nPin: " . $set->pin . "\n1.Proceed\n2.Cancel";
        return response()->json([
            'data' => $msg,
            'endofsession' => 1, //1 means end
        ]);

    }

    private function pin4(Request $request)
    {
        $set = Ussd_session::where('session', $request->sessionid)->where('msisdn', $request->msisdn)->first();
        Log::info($set);

        $set->screen = 3;
        $set->level = 12;

        $set->save();

        if ($request->input == 1) {
            //call the method or endpoint for payment 
            $pay = $this->pay($set->msisdn, $set->amount, $set->pin);

            //if successful
            $msg = "Successful";
            return response()->json([
                'data' => $msg,
                'endofsession' => 1
            ]);
        } else {

            $msg = "You have cancelled.\nTry again later!";
            return response()->json([
                'data' => $msg,
                'endofsession' => 2
            ]);
        }
    }