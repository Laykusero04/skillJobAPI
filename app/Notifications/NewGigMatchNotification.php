<?php

namespace App\Notifications;

use App\Models\Gig;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewGigMatchNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Gig $gig,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'gig_id' => $this->gig->id,
            'title' => $this->gig->title,
            'location' => $this->gig->location,
            'pay' => $this->gig->pay,
            'start_at' => $this->gig->start_at->toIso8601String(),
            'employer_name' => $this->gig->employer->first_name . ' ' . $this->gig->employer->last_name,
            'primary_skill' => $this->gig->primarySkill->name,
            'message' => "A new gig \"{$this->gig->title}\" matches your skills.",
        ];
    }
}
