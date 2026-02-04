<?php

namespace App\Http\Controllers;

use App\Models\Penalty;
use App\Models\PenaltyAppeal;
use Illuminate\Http\Request;

class PenaltyController extends Controller
{
    /**
     * Max warnings before escalation (configurable).
     */
    private const MAX_WARNINGS = 3;

    /**
     * List the authenticated freelancer's penalties.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $penalties = $user->penalties()
            ->with(['gig:id,title,start_at,employer_id', 'gig.employer:id,first_name,last_name', 'appeal'])
            ->orderBy('issued_at', 'desc')
            ->paginate($request->input('per_page', 15));

        $totalPenalties = $user->penalties()->count();

        $penalties->getCollection()->transform(function ($penalty) use ($totalPenalties) {
            return $this->formatPenalty($penalty, $totalPenalties);
        });

        $response = $penalties->toArray();
        $response['warning_summary'] = [
            'current_warnings' => $totalPenalties,
            'max_warnings' => self::MAX_WARNINGS,
        ];

        return response()->json($response);
    }

    /**
     * Show a single penalty with full details.
     */
    public function show(Request $request, Penalty $penalty)
    {
        if ($penalty->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $penalty->load(['gig:id,title,start_at,employer_id', 'gig.employer:id,first_name,last_name', 'appeal']);

        $totalPenalties = $request->user()->penalties()->count();

        return response()->json($this->formatPenalty($penalty, $totalPenalties));
    }

    /**
     * Submit an appeal for a penalty.
     */
    public function appeal(Request $request, Penalty $penalty)
    {
        if ($penalty->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($penalty->appeal()->exists()) {
            return response()->json(['message' => 'Appeal already submitted for this penalty.'], 422);
        }

        $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $appeal = $penalty->appeal()->create([
            'message' => $request->message,
            'status' => 'pending',
        ]);

        $penalty->load(['gig:id,title,start_at,employer_id', 'gig.employer:id,first_name,last_name', 'appeal']);

        $totalPenalties = $request->user()->penalties()->count();

        return response()->json($this->formatPenalty($penalty, $totalPenalties), 201);
    }

    /**
     * Format a penalty for API response.
     */
    private function formatPenalty(Penalty $penalty, int $totalPenalties): array
    {
        $gig = $penalty->gig;
        $appeal = $penalty->appeal;

        $nextPenalty = null;
        if ($totalPenalties >= self::MAX_WARNINGS) {
            $nextPenalty = 'Account suspension';
        } elseif ($totalPenalties === self::MAX_WARNINGS - 1) {
            $nextPenalty = 'Temporary restriction for 7 days';
        }

        return [
            'id' => $penalty->id,
            'gig_id' => $penalty->gig_id,
            'gig_title' => $gig?->title,
            'company' => $gig?->employer ? trim($gig->employer->first_name . ' ' . $gig->employer->last_name) : null,
            'gig_date' => $gig?->start_at?->toDateString(),
            'reason' => $penalty->reason,
            'description' => $penalty->description,
            'issued_at' => $penalty->issued_at,
            'current_warnings' => $totalPenalties,
            'max_warnings' => self::MAX_WARNINGS,
            'next_penalty' => $nextPenalty,
            'is_appealed' => $appeal !== null,
            'appeal_status' => $appeal?->status,
            'created_at' => $penalty->created_at,
            'updated_at' => $penalty->updated_at,
        ];
    }
}
