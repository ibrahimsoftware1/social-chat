<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('user');

        $user->givePermissionTo('create conversations');

        event(new Registered($user));

        return $this->success(
            'Registered successfully, Please verify your email address',
            new UserResource($user),
            201);
    }
    public function login(LoginRequest $request){

            if(!Auth::attempt($request->only('email','password'))){
                return $this->error('Invalid credentials',401);
            }
            $user=User::where('email',$request->email)->first();

            if(!$user->hasVerifiedEmail()){
                Auth::logout();
                return $this->error('Please verify your email address',
                    ['email_verified'=>false],403);
            }

            $token=$user->createToken('auth_token')->plainTextToken;
            $user->markAsOnline();

            return $this->success('Logged in successfully',
                [
                    'user'=>new UserResource($user->load('roles','permissions')),
                    'access_token'=>$token,

                ]);

        }

        //Email verification
    public function verifyEmail(Request $request ,$id , $hash){

        $user=User::findOrFail($id);

        if(!hash_equals($hash,sha1($user->getEmailForVerification()))){
            return $this->error('Invalid verification Link',400);
        }
        if($user->hasVerifiedEmail()){
            return $this->error('Email already verified',400);
        }
        if($user->markEmailAsVerified()){
        event(new Verified($user));
        }
        return $this->success('Email verified successfully',
        ['verified'=>true]);
        }


        //Resend verification email

    public function resendVerificationEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        $user=User::where('email',$request->email)->first();

        if($user->hasVerifiedEmail()){
            return $this->error('Email already verified',400);
        }

        $user->sendEmailVerificationNotification();
        return $this->success('Verification email resent successfully');

    }
    //forget Password
    public function forgotPassword(Request $request){
        $request->validate([
            'email'=>'required|email|exists:users,email',
        ]);

        $status=Password::sendResetLink($request->only('email'));
        if($status===Password::RESET_LINK_SENT){
            return $this->success('Password reset link sent Successfully');
        }
        return $this->error('Invalid email address',400);
    }

    //Reset Password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success('Password reset successfully');
        }

        return $this->error('Invalid token or expired link', 400);
    }

    //Logout

    public function Logout(Request $request){
        $user=User::where('is_online',true);
        $request->user()->markAsOffline();
        $request->user()->currentAccessToken()->delete();
        return $this->success('Logged out successfully');
    }

}

