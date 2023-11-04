<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiBaseController;
use Illuminate\Http\Request;
use JWTAuth;
use Validator;

class UserProfileController extends ApiBaseController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // Validate the incoming data, specifically the "token" field
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return $this->responseHelper->error($validator->errors(), 422);
        }

        try {
            // Attempt to authenticate the user using the token
            $user = JWTAuth::authenticate($request->token);

            if (!$user) {
                return $this->responseHelper->error('User not found', 404);
            }

            return $this->responseHelper->success(['user' => $user]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return $this->responseHelper->error('Unauthorized', 401);
        }
    }
}
