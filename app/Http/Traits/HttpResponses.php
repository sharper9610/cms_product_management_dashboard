<?php
namespace App\Http\Traits;
use App\Models\Student;
use Illuminate\Support\Facades\Redirect;

trait HttpResponses {



    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */


    protected function checkPageAccess($accessName)
    {
        if (is_null(auth()->user()) || !auth()->user()->can($accessName)) {
            abort(403, 'Sorry !! You are Unauthorized!');
        }

    }



    protected function jsonResponse(  $type=true, $message='', $data, $code=200)
    {
        $response = [
            'success' => $type,
            'data'    => $data,
            'message' => $message,
        ];


        return response()->json($response, $code);
    }





}
