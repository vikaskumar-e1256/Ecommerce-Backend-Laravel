<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JWTAuth;

class UserProfileController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'token' => 'required'
        ]);

        $user = JWTAuth::authenticate($request->token);
        return response()->json(['user' => $user]); 
    }
}
