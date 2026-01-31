<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreelancerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'resume_url',
        'resume_uploaded_at',
        'availability',
        'available_today',
        'avg_rating',
        'completed_gigs',
        'no_shows',
    ];

    protected function casts(): array
    {
        return [
            'available_today' => 'boolean',
            'avg_rating' => 'decimal:2',
            'completed_gigs' => 'integer',
            'no_shows' => 'integer',
            'resume_uploaded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
