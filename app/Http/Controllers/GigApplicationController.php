<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\GigStatus;
use App\Models\Gig;
use App\Models\GigApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GigApplicationController extends Controller
{
    /**
     * List applications for a gig (employer only).
     */
    public function index(Request $request, Gig $gig)
    {
        if ($gig->employer_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $query = $gig->applications()->with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($applications);
    }

    /**
     * Apply to a gig (freelancer only).
     */
    public function store(Request $request, Gig $gig)
    {
        $user = $request->user();

        if ($gig->status !== GigStatus::Open) {
            return response()->json(['message' => 'This gig is not accepting applications.'], 422);
        }

        // Check for duplicate application
        $existing = $gig->applications()->where('user_id', $user->id)->exists();
        if ($existing) {
            return response()->json(['message' => 'You have already applied to this gig.'], 422);
        }

        // Validate requirement_confirmations
        $request->validate([
            'requirement_confirmations' => ['nullable', 'array'],
            'requirement_confirmations.*' => ['boolean'],
        ]);

        $confirmations = null;

        if (is_array($gig->requirements) && count($gig->requirements) > 0) {
            $confirmations = $request->requirement_confirmations;

            if (!is_array($confirmations) || count($confirmations) !== count($gig->requirements)) {
                return response()->json([
                    'message' => 'requirement_confirmations length must match gig requirements.',
                ], 422);
            }

            if (in_array(false, $confirmations, true)) {
                return response()->json([
                    'message' => 'All requirements must be confirmed to apply.',
                ], 422);
            }
        }

        // Use transaction with lock to prevent race condition on spots
        $application = DB::transaction(function () use ($gig, $user, $confirmations) {
            $gig->lockForUpdate()->first();

            $acceptedCount = $gig->applications()
                ->where('status', ApplicationStatus::Accepted->value)
                ->count();

            $spotsLeft = $gig->workers_needed - $acceptedCount;

            if ($spotsLeft <= 0) {
                return null;
            }

            return GigApplication::create([
                'gig_id' => $gig->id,
                'user_id' => $user->id,
                'status' => ApplicationStatus::Pending,
                'requirement_confirmations' => $confirmations,
            ]);
        });

        if (!$application) {
            return response()->json(['message' => 'No spots left for this gig.'], 422);
        }

        $application->load('user', 'gig');

        return response()->json($application, 201);
    }

    /**
     * Update application status (employer only).
     */
    public function updateStatus(Request $request, Gig $gig, GigApplication $application)
    {
        if ($gig->employer_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($application->gig_id !== $gig->id) {
            return response()->json(['message' => 'Application does not belong to this gig.'], 404);
        }

        $request->validate([
            'status' => ['required', 'string', 'in:accepted,cancelled'],
        ]);

        $newStatus = ApplicationStatus::from($request->status);

        // If accepting, check spots_left within a transaction
        if ($newStatus === ApplicationStatus::Accepted) {
            $result = DB::transaction(function () use ($gig, $application) {
                $gig->lockForUpdate()->first();

                $acceptedCount = $gig->applications()
                    ->where('status', ApplicationStatus::Accepted->value)
                    ->count();

                $spotsLeft = $gig->workers_needed - $acceptedCount;

                if ($spotsLeft <= 0) {
                    return false;
                }

                $application->update(['status' => ApplicationStatus::Accepted]);

                // Check if gig is now filled
                $newAccepted = $acceptedCount + 1;
                if ($newAccepted >= $gig->workers_needed) {
                    $gig->update(['status' => GigStatus::Filled]);
                }

                return true;
            });

            if (!$result) {
                return response()->json(['message' => 'No spots left. Cannot accept more applicants.'], 422);
            }
        } else {
            $wasAccepted = $application->status === ApplicationStatus::Accepted;
            $application->update(['status' => $newStatus]);

            // If cancelling an accepted application, reopen gig if it was filled
            if ($wasAccepted && $gig->status === GigStatus::Filled) {
                $gig->update(['status' => GigStatus::Open]);
            }
        }

        $application->refresh();
        $application->load('user');

        return response()->json($application);
    }
}
