<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\RestorePasswordRequest;
use App\Http\Resources\Auth\ProfileResource;
use App\Models\User;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseController
{

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $user = new User;

        foreach ($data as $key => $value) {
            $user->$key = $value;
        }
        $user->name = $data['company'];
        $user->password = bcrypt($data['password']);
        $user->save();

        return $user;
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
    public function profile()
    {
        return $this->sendResponse(ProfileResource::make(auth()->user())->resolve(), 'authenticated');
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return $this->sendResponse([], 'logged out');
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
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

    public function checkVerifyToken(\Illuminate\Http\Request $request)
    {
        try {
            $token = $request['token'];
            $user = auth()->payload();

            return $this->sendResponse($token, 'ok');
        } catch (JWTException  $e) {
            return $this->sendError('Token is invalid', [$e->getMessage()]);
        }
    }

    public function submitEmail() {

    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
