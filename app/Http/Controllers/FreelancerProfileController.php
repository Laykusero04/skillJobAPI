<?php

namespace App\Http\Controllers;

use App\Models\FreelancerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FreelancerProfileController extends Controller
{
    /**
     * Get the authenticated freelancer's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $profile = FreelancerProfile::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        return response()->json([
            'freelancer_profile' => [
                'bio' => $profile->bio,
                'resume_url' => $profile->resume_url,
                'resume_uploaded_at' => $profile->resume_uploaded_at?->toIso8601String(),
                'availability' => $profile->availability,
                'available_today' => $profile->available_today,
                'avg_rating' => $profile->avg_rating,
                'completed_gigs' => $profile->completed_gigs,
                'no_shows' => $profile->no_shows,
            ],
        ], 200);
    }

    /**
     * Update the authenticated freelancer's profile.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'bio' => 'sometimes|nullable|string|max:5000',
            'availability' => 'sometimes|nullable|string|max:255',
            'available_today' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        $profile = FreelancerProfile::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        $profile->update($validated);

        return response()->json([
            'freelancer_profile' => [
                'bio' => $profile->bio,
                'resume_url' => $profile->resume_url,
                'resume_uploaded_at' => $profile->resume_uploaded_at?->toIso8601String(),
                'availability' => $profile->availability,
                'available_today' => $profile->available_today,
                'avg_rating' => $profile->avg_rating,
                'completed_gigs' => $profile->completed_gigs,
                'no_shows' => $profile->no_shows,
            ],
        ], 200);
    }

    /**
     * Upload a resume file for the authenticated freelancer.
     */
    public function uploadResume(Request $request)
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB max
        ]);

        $user = $request->user();

        $profile = FreelancerProfile::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        // Delete old resume if it exists
        if ($profile->resume_url) {
            $oldPath = str_replace('/storage/', '', $profile->resume_url);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('resume')->store('resumes', 'public');
        $url = '/storage/' . $path;

        $profile->update([
            'resume_url' => $url,
            'resume_uploaded_at' => now(),
        ]);

        return response()->json([
            'resume_url' => $profile->resume_url,
            'resume_uploaded_at' => $profile->resume_uploaded_at->toIso8601String(),
        ], 200);
    }
}
