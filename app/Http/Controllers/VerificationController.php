<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * Get the current verification status of the authenticated user.
     */
    public function status(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'email_verified' => !is_null($user->email_verified_at),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'phone_verified' => !is_null($user->phone_verified_at),
            'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
        ]);
    }

    /**
     * Manually verify the authenticated user's email.
     * This is a temporary endpoint for testing purposes.
     * Replace with actual email verification flow in production.
     */
    public function verifyEmail(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email is already verified.',
                'email_verified' => true,
                'email_verified_at' => $user->email_verified_at->toIso8601String(),
            ]);
        }

        $user->email_verified_at = now();
        $user->save();

        return response()->json([
            'message' => 'Email verified successfully.',
            'email_verified' => true,
            'email_verified_at' => $user->email_verified_at->toIso8601String(),
        ]);
    }

    /**
     * Manually verify the authenticated user's phone number.
     * This is a temporary endpoint for testing purposes.
     * Replace with SMS verification service in production.
     */
    public function verifyPhone(Request $request)
    {
        $request->validate([
            'phone_number' => 'sometimes|string|max:20',
        ]);

        $user = $request->user();

        // Update phone number if provided
        if ($request->has('phone_number')) {
            $user->phone_number = $request->phone_number;
        }

        if (!$user->phone_number) {
            return response()->json([
                'message' => 'Phone number is required.',
            ], 422);
        }

        if ($user->phone_verified_at) {
            return response()->json([
                'message' => 'Phone number is already verified.',
                'phone_verified' => true,
                'phone_verified_at' => $user->phone_verified_at->toIso8601String(),
            ]);
        }

        $user->phone_verified_at = now();
        $user->save();

        return response()->json([
            'message' => 'Phone number verified successfully.',
            'phone_verified' => true,
            'phone_verified_at' => $user->phone_verified_at->toIso8601String(),
            'phone_number' => $user->phone_number,
        ]);
    }
}
