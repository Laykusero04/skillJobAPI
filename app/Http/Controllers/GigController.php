<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\GigStatus;
use App\Http\Requests\StoreGigRequest;
use App\Http\Requests\UpdateGigRequest;
use App\Jobs\NotifyMatchingFreelancers;
use App\Models\Gig;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GigController extends Controller
{
    /**
     * List gigs.
     * Employer (role 2): sees own gigs, filterable by status.
     * Freelancer (role 3): sees open gigs with filters.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 2) {
            return $this->employerIndex($request);
        }

        return $this->freelancerIndex($request);
    }

    /**
     * Create a new gig.
     */
    public function store(StoreGigRequest $request)
    {
        $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
        $endAt = Carbon::parse($request->date . ' ' . $request->end_time);

        $autoCloseAt = null;
        if ($request->auto_close_enabled && $request->auto_close_date && $request->auto_close_time) {
            $autoCloseAt = Carbon::parse($request->auto_close_date . ' ' . $request->auto_close_time);
        }

        $gig = $request->user()->gigs()->create([
            'title' => $request->title,
            'primary_skill_id' => $request->primary_skill_id,
            'location' => $request->location,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'pay' => $request->pay,
            'workers_needed' => $request->workers_needed,
            'description' => $request->description,
            'auto_close_enabled' => $request->auto_close_enabled ?? false,
            'auto_close_at' => $autoCloseAt,
            'status' => GigStatus::Open,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'app_saving_percent' => $request->app_saving_percent ?? 0,
            'requirements' => $request->requirements,
        ]);

        if ($request->supporting_skill_ids) {
            $gig->supportingSkills()->attach($request->supporting_skill_ids);
        }

        $gig->load('primarySkill', 'supportingSkills', 'employer');
        $gig->loadCount([
            'applications as applicants_count',
            'applications as accepted_applications_count' => function ($query) {
                $query->where('status', ApplicationStatus::Accepted->value);
            },
        ]);

        NotifyMatchingFreelancers::dispatch($gig);

        return response()->json($gig, 201);
    }

    /**
     * Show a single gig.
     */
    public function show(Request $request, Gig $gig)
    {
        $user = $request->user();

        // Employer can only see their own gigs
        if ($user->role === 2 && $gig->employer_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Freelancer can only see open gigs
        if ($user->role === 3 && $gig->status !== GigStatus::Open) {
            return response()->json(['message' => 'This gig is no longer available.'], 404);
        }

        $gig->load('primarySkill', 'supportingSkills', 'employer');
        $gig->loadCount([
            'applications as applicants_count',
            'applications as accepted_applications_count' => function ($query) {
                $query->where('status', ApplicationStatus::Accepted->value);
            },
        ]);

        $response = $gig->toArray();

        // Add is_bookmarked and has_applied for freelancers
        if ($user->role === 3) {
            $response['is_bookmarked'] = $gig->bookmarks()->where('user_id', $user->id)->exists();
            $response['has_applied'] = $gig->applications()->where('user_id', $user->id)->exists();
        }

        return response()->json($response);
    }

    /**
     * Update a gig.
     */
    public function update(UpdateGigRequest $request, Gig $gig)
    {
        $data = $request->only([
            'title', 'primary_skill_id', 'location', 'pay',
            'workers_needed', 'description', 'auto_close_enabled',
            'latitude', 'longitude', 'app_saving_percent', 'requirements',
        ]);

        // Recombine date+time if provided
        $date = $request->date ?? $gig->start_at->toDateString();
        $startTime = $request->start_time ?? $gig->start_at->format('H:i');
        $endTime = $request->end_time ?? $gig->end_at->format('H:i');

        if ($request->hasAny(['date', 'start_time'])) {
            $data['start_at'] = Carbon::parse($date . ' ' . $startTime);
        }
        if ($request->hasAny(['date', 'end_time'])) {
            $data['end_at'] = Carbon::parse($date . ' ' . $endTime);
        }

        if ($request->has('auto_close_enabled')) {
            if ($request->auto_close_enabled && $request->auto_close_date && $request->auto_close_time) {
                $data['auto_close_at'] = Carbon::parse($request->auto_close_date . ' ' . $request->auto_close_time);
            } elseif (!$request->auto_close_enabled) {
                $data['auto_close_at'] = null;
            }
        }

        $gig->update($data);

        if ($request->has('supporting_skill_ids')) {
            $gig->supportingSkills()->sync($request->supporting_skill_ids ?? []);
        }

        $gig->load('primarySkill', 'supportingSkills', 'employer');
        $gig->loadCount([
            'applications as applicants_count',
            'applications as accepted_applications_count' => function ($query) {
                $query->where('status', ApplicationStatus::Accepted->value);
            },
        ]);

        return response()->json($gig);
    }

    /**
     * Soft-delete a gig.
     */
    public function destroy(Request $request, Gig $gig)
    {
        if ($gig->employer_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $gig->delete();

        return response()->json(['message' => 'Gig deleted successfully.']);
    }

    /**
     * Close a gig (employer only).
     */
    public function close(Request $request, Gig $gig)
    {
        if ($gig->employer_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (!in_array($gig->status, [GigStatus::Open, GigStatus::Filled])) {
            return response()->json(['message' => 'This gig cannot be closed.'], 422);
        }

        $gig->update(['status' => GigStatus::Closed]);

        $gig->load('primarySkill', 'supportingSkills', 'employer');
        $gig->loadCount([
            'applications as applicants_count',
            'applications as accepted_applications_count' => function ($query) {
                $query->where('status', ApplicationStatus::Accepted->value);
            },
        ]);

        return response()->json($gig);
    }

    /**
     * Update workers_needed (employer only).
     */
    public function updateWorkers(Request $request, Gig $gig)
    {
        if ($gig->employer_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'workers_needed' => ['required', 'integer', 'min:1'],
        ]);

        $acceptedCount = $gig->applications()
            ->where('status', ApplicationStatus::Accepted->value)
            ->count();

        if ($request->workers_needed < $acceptedCount) {
            return response()->json([
                'message' => 'Workers needed cannot be less than the number of accepted applicants (' . $acceptedCount . ').',
            ], 422);
        }

        $gig->update(['workers_needed' => $request->workers_needed]);

        // If spots are now filled, update status
        if ($request->workers_needed === $acceptedCount && $gig->status === GigStatus::Open) {
            $gig->update(['status' => GigStatus::Filled]);
        } elseif ($request->workers_needed > $acceptedCount && $gig->status === GigStatus::Filled) {
            $gig->update(['status' => GigStatus::Open]);
        }

        $gig->refresh();
        $gig->load('primarySkill', 'supportingSkills', 'employer');
        $gig->loadCount([
            'applications as applicants_count',
            'applications as accepted_applications_count' => function ($query) {
                $query->where('status', ApplicationStatus::Accepted->value);
            },
        ]);

        return response()->json($gig);
    }

    /**
     * Employer index: own gigs with status filter.
     */
    private function employerIndex(Request $request)
    {
        $query = $request->user()->gigs()
            ->with('primarySkill', 'supportingSkills')
            ->withCount([
                'applications as applicants_count',
                'applications as accepted_applications_count' => function ($query) {
                    $query->where('status', ApplicationStatus::Accepted->value);
                },
            ]);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $gigs = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($gigs);
    }

    /**
     * Freelancer index: open gigs with filters.
     */
    private function freelancerIndex(Request $request)
    {
        $query = Gig::where('status', GigStatus::Open->value)
            ->with('primarySkill', 'supportingSkills', 'employer')
            ->withCount([
                'applications as applicants_count',
                'applications as accepted_applications_count' => function ($query) {
                    $query->where('status', ApplicationStatus::Accepted->value);
                },
            ]);

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Filter by skill (primary or supporting)
        if ($request->filled('skill_id')) {
            $skillId = $request->skill_id;
            $query->where(function ($q) use ($skillId) {
                $q->where('primary_skill_id', $skillId)
                    ->orWhereHas('supportingSkills', function ($sq) use ($skillId) {
                        $sq->where('skills.id', $skillId);
                    });
            });
        }

        // Filter by pay range
        if ($request->filled('min_pay')) {
            $query->where('pay', '>=', $request->min_pay);
        }
        if ($request->filled('max_pay')) {
            $query->where('pay', '<=', $request->max_pay);
        }

        // Filter by time slot
        if ($request->filled('time_slot')) {
            switch ($request->time_slot) {
                case 'morning':
                    $query->whereTime('start_at', '>=', '06:00')->whereTime('start_at', '<', '12:00');
                    break;
                case 'afternoon':
                    $query->whereTime('start_at', '>=', '12:00')->whereTime('start_at', '<', '18:00');
                    break;
                case 'evening':
                    $query->whereTime('start_at', '>=', '18:00');
                    break;
            }
        }

        // Filter by distance (radius in km)
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $radiusKm = $request->input('radius_km', 50);
            $query->nearby((float) $request->latitude, (float) $request->longitude, (float) $radiusKm);
        }

        // Filter by new only (created in last 24 hours)
        if ($request->boolean('new_only')) {
            $query->where('created_at', '>=', now()->subDay());
        }

        // Add is_bookmarked and has_applied for the current user
        $userId = $request->user()->id;
        $query->withCount([
            'bookmarks as is_bookmarked' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            },
            'applications as has_applied' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            },
        ]);

        $gigs = $query->orderBy('created_at', 'desc')->paginate(15);

        // Cast is_bookmarked and has_applied from count to boolean
        $gigs->getCollection()->transform(function ($gig) {
            $gig->is_bookmarked = (bool) $gig->is_bookmarked;
            $gig->has_applied = (bool) $gig->has_applied;
            return $gig;
        });

        return response()->json($gigs);
    }
}
