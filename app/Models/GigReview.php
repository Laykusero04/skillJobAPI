<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GigReview extends Model
{
    protected $fillable = [
        'gig_id',
        'employer_id',
        'freelancer_id',
        'application_id',
        'rating',
        'review',
        'earnings',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'earnings' => 'decimal:2',
        ];
    }

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(GigApplication::class, 'application_id');
    }
}
