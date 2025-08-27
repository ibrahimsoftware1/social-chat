<?php

namespace App\Traits;

trait ApiResponse
{
    public function ok($message, $data = null){
        return $this->success($message, $data, 200);
    }

    public function success($message, $data = null, $statusCode=200)
    {
        $response = [
            'status' => $statusCode,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    public function error($errors,$data=null,$statusCode=400){

        if(!is_null($data)){
            $response['data'] = $data;
        }

        return response()->json([
            'errors' => $errors,
            'status' => $statusCode
        ], $statusCode);


    }
}
