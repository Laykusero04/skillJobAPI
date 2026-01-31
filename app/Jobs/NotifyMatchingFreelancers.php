<?php

namespace App\Jobs;

use App\Models\Gig;
use App\Models\User;
use App\Notifications\NewGigMatchNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyMatchingFreelancers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private Gig $gig,
    ) {}

    public function handle(): void
    {
        $this->gig->load('employer', 'primarySkill', 'supportingSkills');

        // Collect all skill IDs for this gig (primary + supporting)
        $skillIds = $this->gig->supportingSkills->pluck('id')
            ->push($this->gig->primary_skill_id)
            ->unique()
            ->values();

        // Find freelancers (role=3) who have at least one matching skill,
        // excluding the employer who created the gig.
        $freelancers = User::where('role', 3)
            ->where('id', '!=', $this->gig->employer_id)
            ->whereHas('skills', function ($query) use ($skillIds) {
                $query->whereIn('skills.id', $skillIds);
            })
            ->get();

        if ($freelancers->isEmpty()) {
            return;
        }

        Notification::send($freelancers, new NewGigMatchNotification($this->gig));
    }
}
