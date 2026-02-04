<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Conversation extends Model
{
    protected $fillable = [
        'gig_id',
        'employer_id',
        'freelancer_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    // Relationships

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(MessageReport::class, 'reportable');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    // Helpers

    public function isParticipant(int $userId): bool
    {
        return $this->employer_id === $userId || $this->freelancer_id === $userId;
    }

    public function otherUser(int $currentUserId): BelongsTo
    {
        if ($this->employer_id === $currentUserId) {
            return $this->freelancer();
        }

        return $this->employer();
    }
}
