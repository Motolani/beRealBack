<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Helpers\CurlClass;
use Illuminate\Support\Facades\Log;

class NowNowAPI extends Controller
{
    //1. get token 
    //2. validate
    //3. make request

    private static $BASE_URL;

    private static $KEY;

    private static $AUTHORIZATION;


    public function __construct()
    {
	   self::$BASE_URL = env('NOW_NOW_URL');

	   self::$KEY = env('NOWNOW_TOKEN_KEY');

       self::$AUTHORIZATION = self::getToken();
    }

    //--------------------------------------------  Token Activitiy  -------------------------------------------------------------
    static function getToken()
    {   
        
        $requestId = time() . rand(100, 999);

        $body = array(
                "mfsCommonServiceRequest" => array(
                "mfsSourceInfo" => array(
                "channelId" => "22",
                "surroundSystem" => "3"
                ),
                "mfsTransactionInfo" => array(
                // "requestId" => "1528888797117",
                "requestId" => $requestId,
                "serviceType" => "0",
                "timestamp" => 4 
                )
            )
        );

        $encBody = json_encode($body);
        $key = self::$KEY;
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://nownowpay.com.ng/mfs-transaction-management/authManagement/get',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $encBody,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic '.$key,
            'Content-Type: application/json'
        ),
        ));

        $res = curl_exec($curl);

        curl_close($curl);

        $dec = json_decode($res);
        if(isset($dec)){
            if($dec->mfsCommonServiceResponse->mfsStatusInfo->errorCode == 100){
                $token = $dec->mfsResponseInfo->token;

                return $token;
            }else{
                $res = $dec->mfsCommonServiceResponse->mfsStatusInfo->errorDescription;

                return $res;
            }
        }else{
            // failed to decode
            $res = 'Failed';
            return $res;
        }
    }

    static function validateToken($token)
    {   
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://nownowpay.com.ng/mfs-authorization/oauth/check_token?token='.$token,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $res = curl_exec($curl);

        curl_close($curl);

        $dec = json_decode($res);
        if(isset($dec->error)){
            return response()->json([
                'message' => 'Failed! Generate Token',
                'status' => 300
            ]);
        }else{
            return response()->json([
                'message' => 'Token Valid',
                'data' => $token,
                'status' => 200
            ]);
        }
    }
    //--------------------------------------------  Token Activitiy End  -------------------------------------------------------------

    
    //--------------------------------------------  Disco API  -------------------------------------------------------------
    static function getElectricityProviders(array $body)
    {

        $url = self::$BASE_URL . "/billerManagement/getPackDetail";

        $res = self::Api($body, 'POST', $url);

        return $res;
    } 

    static function verifyElectricity(array $body)
    {
	    Log::info($body);
        $url = self::$BASE_URL . "/billerManagement/queryBill";

        $res = self::Api($body, 'POST', $url);
	    Log::info($res);
        return $res;
    }

    static function electricityPurchase(array $body)
    {
	    Log::info($body);
        $url = self::$BASE_URL . "/billerManagement/payBill";

        $res = self::Api($body, 'POST', $url);
	    Log::info($res);
        return $res;
    }
    //------------------------------------------------  Disco Ends   -------------------------------------------------------------------


    //--------------------------- PayTv -----------------------------------
    static function tvPackages(array $body)
    {

        $url = self::$BASE_URL . "/billerManagement/getPackDetail";

        $res = self::Api($body, 'POST', $url);

        return $res;
    }
  
    static function tvPurchase(array $body)
    {

        $url = self::$BASE_URL . "/billerManagement/payBill";

        $res = self::Api($body, 'POST', $url);
        Log::info($res);
        return $res;
    }

    //No Add on Api
    static function fetchTvAddon(array $body)
    {

        $url = self::$BASE_URL . "/billerManagement/getPackDetail";

        $res = self::Api($body, 'POST', $url);
        Log::info($res);
        return $res;
    }

    //--------------------------- PayTv -----------------------------------


    //--------------------------- Airtime-----------------------------------

    // static function airtimePurchase(array $body)
    // {

    //     $url = self::$BASE_URL . "/billerManagement/payBill";

    //     $res = self::Api($body, 'POST', $url);

    //     return $res;
    // }

    //--------------------------- Airtime Ends -----------------------------------

    
    //--------------------------- Data (Telco) -----------------------------------

    // static function dataBundles(array $body)
    // {
    //     $url = self::$BASE_URL . "billerManagement/getPackDetail";

    //     $res = self::Api($body, 'POST', $url);

    //     return $res;
    // }

    // static function dataPurchase(array $body)
    // {

    //     $url = self::$BASE_URL . "billerManagement/payBill";

    //     $res = self::Api($body, 'POST', $url);

    //     return $res;
    // }

    //--------------------------- Data Ends -----------------------------------

    //--------------------------------------------  General API  -------------------------------------------------------------

    static function  Api($body, $method, $url)
    {
        $payload =  self::payload($body);

        $token = self::$AUTHORIZATION;

        //Validate Token
        $valToken = self::validateToken($token);
        $dec = json_decode($valToken);

        if($dec->status == 300){
            $token = self::getToken(); 
        }else{
            $token = $dec->data;
        }

        $curl = curl_init();
        
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>$payload,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '.$token,
            ),
        ));
        
        $response = curl_exec($curl);
        return $response;
    }

    static function  payload($body)
    {
        return is_array($body) || is_object($body) ? json_encode($body) : $body;
    }

    //----------------------------------------------------------------------------------------------------------------------------------
}
