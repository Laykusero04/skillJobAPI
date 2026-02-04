<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\GigApplication;
use Illuminate\Http\Request;

class FreelancerApplicationController extends Controller
{
    /**
     * List the authenticated freelancer's applications.
     * Supports filtering by status and pagination.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->gigApplications()
            ->with([
                'gig' => function ($q) {
                    $q->with(['employer:id,first_name,last_name,profile_image_url', 'primarySkill:id,name']);
                },
                'review',
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        $applications->getCollection()->transform(function ($application) {
            return $this->formatApplication($application);
        });

        return response()->json($applications);
    }

    /**
     * Show a single application with full details.
     */
    public function show(Request $request, GigApplication $application)
    {
        if ($application->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $application->load([
            'gig' => function ($q) {
                $q->with(['employer:id,first_name,last_name,profile_image_url,phone_number,email', 'primarySkill:id,name']);
            },
            'review',
        ]);

        return response()->json($this->formatApplication($application));
    }

    /**
     * Withdraw a pending application.
     */
    public function withdraw(Request $request, GigApplication $application)
    {
        if ($application->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($application->status !== ApplicationStatus::Pending) {
            return response()->json(['message' => 'Only pending applications can be withdrawn.'], 422);
        }

        $application->update(['status' => ApplicationStatus::Cancelled]);

        $application->load([
            'gig' => function ($q) {
                $q->with(['employer:id,first_name,last_name,profile_image_url', 'primarySkill:id,name']);
            },
        ]);

        return response()->json($this->formatApplication($application));
    }

    /**
     * Get summary counts per status for the freelancer's applications.
     */
    public function counts(Request $request)
    {
        $user = $request->user();

        $counts = $user->gigApplications()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'pending' => $counts->get(ApplicationStatus::Pending->value, 0),
            'accepted' => $counts->get(ApplicationStatus::Accepted->value, 0),
            'rejected' => $counts->get(ApplicationStatus::Rejected->value, 0),
            'completed' => $counts->get(ApplicationStatus::Completed->value, 0),
            'cancelled' => $counts->get(ApplicationStatus::Cancelled->value, 0),
        ]);
    }

    /**
     * Get completed applications summary (total earnings, avg rating, count).
     */
    public function completedSummary(Request $request)
    {
        $user = $request->user();

        $completedApplications = $user->gigApplications()
            ->where('status', ApplicationStatus::Completed->value)
            ->with(['gig', 'review'])
            ->get();

        $totalEarnings = $completedApplications->sum(function ($application) {
            if ($application->review) {
                return $application->review->earnings;
            }
            return $application->gig ? $application->gig->freelancer_pay : 0;
        });

        $reviews = $completedApplications->pluck('review')->filter();
        $avgRating = $reviews->isNotEmpty()
            ? round($reviews->avg('rating'), 2)
            : null;

        $profile = $user->freelancerProfile;

        return response()->json([
            'completed_count' => $completedApplications->count(),
            'total_earnings' => round($totalEarnings, 2),
            'avg_rating' => $avgRating ?? ($profile ? $profile->avg_rating : null),
        ]);
    }

    /**
     * Format an application for API response.
     */
    private function formatApplication(GigApplication $application): array
    {
        $gig = $application->gig;

        $data = [
            'id' => $application->id,
            'gig_id' => $application->gig_id,
            'user_id' => $application->user_id,
            'status' => $application->status->value,
            'rejection_reason' => $application->rejection_reason,
            'created_at' => $application->created_at,
            'updated_at' => $application->updated_at,
        ];

        if ($gig) {
            $employer = $gig->employer;

            $data['gig'] = [
                'id' => $gig->id,
                'title' => $gig->title,
                'location' => $gig->location,
                'latitude' => $gig->latitude,
                'longitude' => $gig->longitude,
                'start_at' => $gig->start_at,
                'end_at' => $gig->end_at,
                'pay' => $gig->pay,
                'rate_per_hour' => $gig->rate_per_hour,
                'freelancer_pay' => $gig->freelancer_pay,
                'workers_needed' => $gig->workers_needed,
                'status' => $gig->status->value,
                'employer' => $employer ? [
                    'id' => $employer->id,
                    'first_name' => $employer->first_name,
                    'last_name' => $employer->last_name,
                    'name' => trim($employer->first_name . ' ' . $employer->last_name),
                    'profile_image_url' => $employer->profile_image_url,
                ] : null,
                'primary_skill' => $gig->primarySkill ? [
                    'id' => $gig->primarySkill->id,
                    'name' => $gig->primarySkill->name,
                ] : null,
            ];
        }

        $review = $application->relationLoaded('review') ? $application->review : null;
        if ($review) {
            $data['review'] = [
                'id' => $review->id,
                'rating' => $review->rating,
                'review' => $review->review,
                'earnings' => $review->earnings,
                'created_at' => $review->created_at,
            ];
        }

        return $data;
    }
}
