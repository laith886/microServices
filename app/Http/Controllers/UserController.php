<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
class UserController extends Controller
{
    public function Register(StoreUserRequest $request)
    {
        $validatedData = $request->validated();

        $path = null;
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $validatedData['profile_image'] = $path;
        }
        $user = User::create($validatedData);

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
        ], 201);
    }

    public function Login(Request $request){
        $request->validate([
            'phone'=>'required|string',
            'password'=>'required|string|min:8'
        ]);

        if(!Auth::attempt($request->only('phone','password')))
        {
            return response()->json(['message'=>'invalid email or password'],401);
        }

        $user=User::where('phone',$request->phone)->firstorFail();

        $token=$user->createToken('auth_Token')->plainTextToken;

        return response()->json(['success'=>true,$token],200);
    }

    public function Logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message'=>'Logout success',200]);
    }



    public function GetUserID(User $user){


        return $user->id;
    }



    public function GetUser(){
        if (auth()->check()) {
            $user = auth()->user();
            return $user;
        } else {
            return 'User not found';
        }
    }



}
