<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\GigStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gig extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employer_id',
        'title',
        'primary_skill_id',
        'location',
        'start_at',
        'end_at',
        'pay',
        'workers_needed',
        'description',
        'auto_close_enabled',
        'auto_close_at',
        'status',
        'latitude',
        'longitude',
        'app_saving_percent',
    ];

    protected $appends = [
        'spots_left',
        'duration',
        'rate_per_hour',
        'app_saving_amount',
        'freelancer_pay',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'pay' => 'decimal:2',
            'workers_needed' => 'integer',
            'auto_close_enabled' => 'boolean',
            'auto_close_at' => 'datetime',
            'status' => GigStatus::class,
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'app_saving_percent' => 'integer',
        ];
    }

    // Relationships

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function primarySkill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'primary_skill_id');
    }

    public function supportingSkills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'gig_supporting_skills');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(GigApplication::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(GigBookmark::class);
    }

    // Query Scopes

    /**
     * Filter gigs within a radius (km) using Haversine formula.
     */
    public function scopeNearby($query, float $latitude, float $longitude, float $radiusKm)
    {
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("{$haversine} <= ?", [$latitude, $longitude, $latitude, $radiusKm])
            ->selectRaw("{$haversine} AS distance", [$latitude, $longitude, $latitude]);
    }

    // Computed Accessors

    protected function spotsLeft(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Use preloaded count if available (from withCount)
                if (isset($this->attributes['accepted_applications_count'])) {
                    return $this->workers_needed - (int) $this->attributes['accepted_applications_count'];
                }

                return $this->workers_needed - $this->applications()
                    ->where('status', ApplicationStatus::Accepted->value)
                    ->count();
            },
        );
    }

    protected function duration(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_at && $this->end_at
                ? round($this->start_at->floatDiffInHours($this->end_at), 2)
                : 0,
        );
    }

    protected function ratePerHour(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->duration > 0
                ? round((float) $this->pay / $this->duration, 2)
                : 0,
        );
    }

    protected function appSavingAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => round((float) $this->pay * ($this->app_saving_percent / 100), 2),
        );
    }

    protected function freelancerPay(): Attribute
    {
        return Attribute::make(
            get: fn () => round((float) $this->pay - $this->app_saving_amount, 2),
        );
    }
}
