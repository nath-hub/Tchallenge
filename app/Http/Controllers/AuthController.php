<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Requests\AuthRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return string
     * 
     */
    public function login(AuthRequest $request)
    {
        $data = $request->validated();

        if (Auth::attempt($data)) {
            $user = $request->user();

            if ($user->email_verified_at === null) {
                return response()->json([
                    'code' => 401,
                    'error' => 'Account not validate in email',
                    'message' => 'Account is not validate, verify your email',
                ], 401);
            } else {

                $token = $user->createToken('API TOKEN');

                return response()->json([
                    'code' => 200,
                    'data' => [
                        'token' => [
                            'type' => 'Bearer',
                            'expires_at' =>  Carbon::parse($token->accessToken->expires_at),
                            'access_token' => $token->plainTextToken
                        ],
                        'user' => [

                            'phone' => $user->phone,
                            'email' => $user->email,
                            'avatar' => $user->avatar,
                            'login' => $user->login,
                        ]
                    ]
                ]);
            }
        }

        return response()->json([
            'code' => 401,
            'error' => 'invalid_client',
            'message' => 'Client authenfication failed',
        ], 401);
    }



    public function LoginWithGoogle()
    {
    }

    public function LoginWithFacebook(Request $request)
    {
        try {
            $user = Socialite::driver('facebook')->user();
     
            $saveUser = User::updateOrCreate([
                'facebook_id' => $user->getId(),
            ],[
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'password' => Hash::make($user->getName().'@'.$user->getId())
                 ]);
     
            Auth::loginUsingId($saveUser->id);
     
            return redirect()->route('home');
            } catch (\Throwable $th) {
               throw $th;
            }
        }
    

    public function logout(Request $request, User $user)
    {

        if ($user->email_verified_at === null) {
            return response()->json([
                'code' => 401,
                'error' => 'Account not validate in email',
                'message' => 'Account is not validate, verify your email',
            ], 401);
        } else {

            Auth::guard('web')->logout();

            $user->tokens()->delete();

            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'code' => 200,
                'data' => $user,
                'message' => 'vous êtes bien déconnecté'
            ]);
        }
    }
}
