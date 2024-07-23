<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\RestorePasswordRequest;
use App\Http\Resources\Auth\ProfileResource;
use App\Models\User;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseController
{
    public function register(RegisterRequest $request)
    {
        try {
            $data = $request->validated();
            $user = new User();

            foreach ($data as $key => $value) {
                $user->$key = $value;
            }
            $user->name = $data['company'];
            $user->role = 'admin';
            $user->password = bcrypt($data['password']);
            $user->save();

            $token = auth()->attempt(['email' => $data['email'], 'password' => $data['password']]);
            $user->submitEmailVerify($token);
            return $this->sendResponse(true, 'registered successfully');
        } catch (JWTException  $e) {
            return $this->sendError('Token is invalid', [$e->getMessage()]);
        }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!$token = auth()->attempt($credentials)) {
            return $this->sendError('Unauthorized', ['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(): \Illuminate\Http\JsonResponse
    {
        return $this->sendResponse(ProfileResource::make(auth()->user())->resolve(), 'authenticated');
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): \Illuminate\Http\JsonResponse
    {
        auth()->logout();

        return $this->sendResponse([], 'logged out');
    }

    public function refresh()
    {
        try {
            return $this->respondWithToken(auth()->refresh());
        } catch (JWTException  $e) {
            return $this->sendError('Invalid token', $e->getMessage(), 401);
        }
    }

    public function resetPassword(RestorePasswordRequest $request)
    {
        $email = $request->validated();
        $user = User::where('email', $email)->first();
        $token = '';

        if ($user){
            $token = JWTAuth::fromUser($user);
        }

        return $user->sendPasswordResetNotification($token);
    }

    public function checkVerifyToken(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $token = $request['token'];
            $user = auth()->setToken($token)->user();

            if (!$user) {
               return $this->sendError('token is invalid', []);
            }
            return $this->sendResponse(true, 'ok');
        } catch (JWTException  $e) {
            return $this->sendError('Token is invalid', [$e->getMessage()]);
        }
    }

    public function submitEmail($token) {
        $user = auth()->setToken($token)->user();

        if (!$user) {
            return $this->sendError('token is invalid', []);
        }
        if ($user->email_verified_at) {
            return $this->sendResponse('this user have already verified email', 'ok');
        }
        $user->email_verified_at = now();
        $user->save();
        return $this->sendResponse(now(), 'ok');
    }

    public function changePassword(ChangePasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $data = $request->validated();

            $token = $data['token'];

            $user = auth()->setToken($token)->user();
            $user->password = bcrypt($request['password']);
            $user->save();
            return $this->sendResponse(true, 'password changed');
        }catch (JWTException  $e) {
            return $this->sendError('Token is invalid', [$e->getMessage()]);
        }
    }

    protected function respondWithToken($token): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
