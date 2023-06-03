<?php

namespace App\Traits;

trait HttpResponses {
    protected function sucess($data, $message = null, $code = 200){
        return response()->json([
            'responseCode' => 200,
            'status' => 'Request Successful',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error($data, $message = null, $code){
        return response()->json([
            'responseCode' => 301,
            'status' => 'Error has occurred...',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function unauthorized($data, $message = null, $code){
        return response()->json([
            'responseCode' => 401,
            'status' => 'Unauthorized Request...',
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}