<?php

namespace App\Console\Commands;

use App\Enums\GigStatus;
use App\Models\Gig;
use Illuminate\Console\Command;

class CompleteGigs extends Command
{
    protected $signature = 'gigs:complete';

    protected $description = 'Mark gigs as completed when their end time has passed';

    public function handle(): void
    {
        $count = Gig::whereIn('status', [GigStatus::Open->value, GigStatus::Filled->value])
            ->where('end_at', '<=', now())
            ->update(['status' => GigStatus::Completed->value]);

        $this->info("Completed {$count} gig(s).");
    }
}
