<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\FreelancerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user and return token
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role ?? 3,
            'phone_number' => $request->phone_number,
            'profile_image_url' => $request->profile_image_url,
        ]);

        // Create token with 7-day expiry
        $token = $user->createToken('auth_token', ['*'], now()->addDays(7))->plainTextToken;
        $expiresAt = now()->addDays(7)->toIso8601String();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role,
                'profile_image_url' => $user->profile_image_url,
                'phone_number' => $user->phone_number,
                'email_verified' => !is_null($user->email_verified_at),
                'phone_verified' => !is_null($user->phone_verified_at),
                'created_at' => $user->created_at->toIso8601String(),
                'updated_at' => $user->updated_at->toIso8601String(),
            ],
            'token' => $token,
            'refresh_token' => null,
            'expires_at' => $expiresAt,
        ], 201);
    }

    /**
     * Login user and return token
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'message' => ['Invalid email or password'],
            ])->status(401);
        }

        // Create token with 7-day expiry
        $token = $user->createToken('auth_token', ['*'], now()->addDays(7))->plainTextToken;
        $expiresAt = now()->addDays(7)->toIso8601String();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role,
                'profile_image_url' => $user->profile_image_url,
                'phone_number' => $user->phone_number,
                'email_verified' => !is_null($user->email_verified_at),
                'phone_verified' => !is_null($user->phone_verified_at),
                'created_at' => $user->created_at->toIso8601String(),
                'updated_at' => $user->updated_at->toIso8601String(),
            ],
            'token' => $token,
            'refresh_token' => null,
            'expires_at' => $expiresAt,
        ], 200);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();

        $response = [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role,
                'profile_image_url' => $user->profile_image_url,
                'phone_number' => $user->phone_number,
                'email_verified' => !is_null($user->email_verified_at),
                'phone_verified' => !is_null($user->phone_verified_at),
                'created_at' => $user->created_at->toIso8601String(),
                'updated_at' => $user->updated_at->toIso8601String(),
                'unread_notifications_count' => $user->unreadNotifications()->count(),
            ],
        ];

        // Include skills and freelancer profile for freelancers
        if ($user->role === 3) {
            $response['skills'] = $user->skills()->orderBy('name')->get();

            $profile = FreelancerProfile::firstOrCreate(
                ['user_id' => $user->id],
                []
            );

            $response['freelancer_profile'] = [
                'bio' => $profile->bio,
                'resume_url' => $profile->resume_url,
                'resume_uploaded_at' => $profile->resume_uploaded_at?->toIso8601String(),
                'availability' => $profile->availability,
                'available_today' => $profile->available_today,
                'avg_rating' => $profile->avg_rating,
                'completed_gigs' => $profile->completed_gigs,
                'no_shows' => $profile->no_shows,
            ];
        }

        return response()->json($response, 200);
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * Get all users
     */
    public function getAllUsers()
    {
        $users = User::orderBy('created_at', 'desc')->get();

        return response()->json([
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'role' => $user->role,
                    'profile_image_url' => $user->profile_image_url,
                    'phone_number' => $user->phone_number,
                    'created_at' => $user->created_at->toIso8601String(),
                    'updated_at' => $user->updated_at->toIso8601String(),
                ];
            })->values(),
        ], 200);
    }
}
