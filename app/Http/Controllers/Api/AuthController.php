<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;    // Import This
use App\Http\Requests\RegisterRequest; // Import This
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // Type Hint হিসেবে RegisterRequest ব্যবহার করুন
    public function register(RegisterRequest $request)
    {
        // $request->validated() শুধুমাত্র ভ্যালিডেট করা ডাটা রিটার্ন করে (Secure)
        $validatedData = $request->validated();

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        $user->assignRole('customer');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
            'role' => 'customer'
        ], 201);
    }

    // Type Hint হিসেবে LoginRequest ব্যবহার করুন
    public function login(LoginRequest $request)
    {
        // এখানে আর ভ্যালিডেশন চেক করার দরকার নেই, লারাভেল অটোমেটিক করবে
        $validatedData = $request->validated();

        $user = User::where('email', $validatedData['email'])->first();

        if (! $user || ! Hash::check($validatedData['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'role' => $user->getRoleNames()->first()
        ]);
    }

    // Logout এর জন্য ডিফল্ট Request ক্লাসই ঠিক আছে
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
