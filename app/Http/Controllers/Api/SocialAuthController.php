<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            $user = User::firstOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name' => $socialUser->getName(),
                    'password' => bcrypt(Str::random(16)), // র‍্যান্ডম পাসওয়ার্ড
                    'email_verified_at' => now(),
                ]
            );

            if ($user->roles->isEmpty()) {
                $user->assignRole('customer');
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $role = $user->getRoleNames()->first();

            $frontendUrl = config('app.frontend_url');

            return redirect("{$frontendUrl}/auth/callback?token={$token}&role={$role}");

        } catch (\Exception $e) {
            return response()->json(['error' => 'Login failed: ' . $e->getMessage()], 500);
        }
    }
}
