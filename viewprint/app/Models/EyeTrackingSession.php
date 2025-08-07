<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Represents a single eye-tracking session where a participant viewed a volume.
 *
 * @property int $id
 * @property int $participant_id
 * @property int $volume_id
 * @property int $duration_ms Total viewing time in milliseconds
 * @property string $raw_data_path Path to CSV/raw eye-tracking data
 * @property string|null $processed_data_path Path to processed NIfTI heatmap
 * @property array $metadata Session-specific data (eye tracker model, sampling rate, etc.)
 * @property \Carbon\Carbon $recorded_at When the session was recorded
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\Participant $participant
 * @property-read \App\Models\Volume $volume
 */
class EyeTrackingSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'participant_id',
        'volume_id',
        'duration_ms',
        'raw_data_path',
        'processed_data_path',
        'metadata',
        'recorded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'recorded_at' => 'datetime',
        'duration_ms' => 'integer',
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
     * Get the participant for this session.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Get the volume that was viewed in this session.
     */
    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class);
    }

    /**
     * Get the duration in seconds.
     */
    public function getDurationSecondsAttribute(): float
    {
        return $this->duration_ms / 1000;
    }

    /**
     * Get human-readable duration.
     */
    public function getDurationHumanAttribute(): string
    {
        $seconds = floor($this->duration_ms / 1000);
        $minutes = floor($seconds / 60);

        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds % 60);
        } else {
            return sprintf('%ds', $seconds);
        }
    }

    /**
     * Check if the session has been processed (heatmap generated).
     */
    public function getIsProcessedAttribute(): bool
    {
        return !is_null($this->processed_data_path) &&
            Storage::exists($this->processed_data_path);
    }

    /**
     * Get metadata value.
     */
    public function getMetadata(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    /**
     * Get eye tracker model from metadata.
     */
    public function getEyeTrackerModelAttribute(): ?string
    {
        return $this->getMetadata('eye_tracker_model');
    }

    /**
     * Get sampling rate from metadata.
     */
    public function getSamplingRateAttribute(): ?int
    {
        return $this->getMetadata('sampling_rate_hz');
    }

    /**
     * Get calibration error from metadata.
     */
    public function getCalibrationErrorAttribute(): ?float
    {
        return $this->getMetadata('calibration_error');
    }

    /**
     * Scope to only processed sessions.
     */
    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_data_path');
    }

    /**
     * Scope to only unprocessed sessions.
     */
    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_data_path');
    }

    /**
     * Scope to sessions recorded within a date range.
     */
    public function scopeRecordedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    /**
     * Scope to sessions for a specific participant group.
     */
    public function scopeForParticipantGroup($query, string $group)
    {
        return $query->whereHas('participant', function ($q) use ($group) {
            $q->where('group', $group);
        });
    }

    /**
     * Mark the session as processed with the generated heatmap path.
     */
    public function markAsProcessed(string $processedPath): self
    {
        $this->processed_data_path = $processedPath;
        $this->save();

        return $this;
    }

    /**
     * Delete associated files when the session is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (EyeTrackingSession $session) {
            // Delete raw data file
            if (Storage::exists($session->raw_data_path)) {
                Storage::delete($session->raw_data_path);
            }

            // Delete processed data file if exists
            if ($session->processed_data_path && Storage::exists($session->processed_data_path)) {
                Storage::delete($session->processed_data_path);
            }
        });
    }
}
