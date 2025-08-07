<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a research participant whose eye-tracking data is being analyzed.
 *
 * @property int $id
 * @property string $code Anonymous participant code (unique)
 * @property string|null $group Participant group (e.g., "expert", "novice")
 * @property array $metadata Flexible participant attributes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\EyeTrackingSession> $eyeTrackingSessions
 */
class Participant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'code',
        'group',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'metadata' => '{}',
    ];

    /**
     * Get all eye-tracking sessions for this participant.
     */
    public function eyeTrackingSessions(): HasMany
    {
        return $this->hasMany(EyeTrackingSession::class);
    }

    /**
     * Get eye-tracking sessions for a specific volume.
     */
    public function sessionsForVolume(Volume $volume): HasMany
    {
        return $this->eyeTrackingSessions()->where('volume_id', $volume->id);
    }

    /**
     * Get participants by group.
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Get expert participants.
     */
    public function scopeExperts($query)
    {
        return $query->where('group', 'expert');
    }

    /**
     * Get novice participants.
     */
    public function scopeNovices($query)
    {
        return $query->where('group', 'novice');
    }

    /**
     * Get a metadata value.
     */
    public function getMetadata(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    /**
     * Set a metadata value.
     */
    public function setMetadata(string $key, $value): self
    {
        $metadata = $this->metadata;
        data_set($metadata, $key, $value);
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Get years of experience from metadata.
     */
    public function getExperienceYearsAttribute(): ?int
    {
        return $this->getMetadata('experience_years');
    }

    /**
     * Get specialty from metadata.
     */
    public function getSpecialtyAttribute(): ?string
    {
        return $this->getMetadata('specialty');
    }

    /**
     * Check if participant has viewed a specific volume.
     */
    public function hasViewedVolume(Volume $volume): bool
    {
        return $this->eyeTrackingSessions()
            ->where('volume_id', $volume->id)
            ->exists();
    }

    /**
     * Get total viewing time across all sessions.
     */
    public function getTotalViewingTimeAttribute(): int
    {
        return $this->eyeTrackingSessions()->sum('duration_ms');
    }

    /**
     * Get total viewing time as human-readable string.
     */
    public function getTotalViewingTimeHumanAttribute(): string
    {
        $ms = $this->total_viewing_time;
        $seconds = floor($ms / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes % 60);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds % 60);
        } else {
            return sprintf('%ds', $seconds);
        }
    }
}
