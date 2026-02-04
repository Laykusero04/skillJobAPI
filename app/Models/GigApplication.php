<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GigApplication extends Model
{
    protected $fillable = [
        'gig_id',
        'user_id',
        'status',
        'requirement_confirmations',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'requirement_confirmations' => 'array',
        ];
    }

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(GigReview::class, 'application_id');
    }
}
