<?php

namespace App\Console\Commands;

use App\Enums\GigStatus;
use App\Models\Gig;
use Illuminate\Console\Command;

class AutoCloseGigs extends Command
{
    protected $signature = 'gigs:auto-close';

    protected $description = 'Close gigs that have reached their auto-close time';

    public function handle(): void
    {
        $count = Gig::where('auto_close_enabled', true)
            ->where('status', GigStatus::Open->value)
            ->whereNotNull('auto_close_at')
            ->where('auto_close_at', '<=', now())
            ->update(['status' => GigStatus::Closed->value]);

        $this->info("Auto-closed {$count} gig(s).");
    }
}
