<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks background calculation jobs for derived layers.
 *
 * @property int $id
 * @property int $workspace_layer_id
 * @property string $status One of: pending, processing, completed, failed
 * @property int $progress Progress percentage (0-100)
 * @property \Carbon\Carbon|null $started_at When processing began
 * @property \Carbon\Carbon|null $completed_at When processing finished
 * @property string|null $error_message Error details if failed
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\WorkspaceLayer $layer
 */
class CalculationJob extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'workspace_layer_id',
        'status',
        'progress',
        'started_at',
        'completed_at',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'progress' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Get the workspace layer this job belongs to.
     */
    public function layer(): BelongsTo
    {
        return $this->belongsTo(WorkspaceLayer::class, 'workspace_layer_id');
    }

    /**
     * Check if the job is pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the job is processing.
     */
    public function getIsProcessingAttribute(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if the job is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the job has failed.
     */
    public function getIsFailedAttribute(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the job is finished (completed or failed).
     */
    public function getIsFinishedAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED]);
    }

    /**
     * Get the processing duration in seconds.
     */
    public function getProcessingDurationAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->completed_at->diffInSeconds($this->started_at);
        }
        return null;
    }

    /**
     * Get human-readable processing duration.
     */
    public function getProcessingDurationHumanAttribute(): ?string
    {
        if ($duration = $this->processing_duration) {
            if ($duration < 60) {
                return "{$duration}s";
            } elseif ($duration < 3600) {
                $minutes = floor($duration / 60);
                $seconds = $duration % 60;
                return "{$minutes}m {$seconds}s";
            } else {
                $hours = floor($duration / 3600);
                $minutes = floor(($duration % 3600) / 60);
                return "{$hours}h {$minutes}m";
            }
        }
        return null;
    }

    /**
     * Mark the job as processing.
     */
    public function markAsProcessing(): self
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
            'progress' => 0,
        ]);

        return $this;
    }

    /**
     * Update the job progress.
     */
    public function updateProgress(int $progress): self
    {
        $this->update([
            'progress' => min(100, max(0, $progress)),
        ]);

        return $this;
    }

    /**
     * Mark the job as completed.
     */
    public function markAsCompleted(): self
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'progress' => 100,
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark the job as failed.
     */
    public function markAsFailed(string $errorMessage): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Retry a failed job.
     */
    public function retry(): self
    {
        if ($this->is_failed) {
            $this->update([
                'status' => self::STATUS_PENDING,
                'progress' => 0,
                'started_at' => null,
                'completed_at' => null,
                'error_message' => null,
            ]);
        }

        return $this;
    }

    /**
     * Get the calculation type from the layer configuration.
     */
    public function getCalculationTypeAttribute(): ?string
    {
        return $this->layer->calculation_type;
    }

    /**
     * Get estimated time remaining based on current progress.
     */
    public function getEstimatedTimeRemainingAttribute(): ?string
    {
        if ($this->is_processing && $this->progress > 0 && $this->started_at) {
            $elapsedSeconds = now()->diffInSeconds($this->started_at);
            $estimatedTotalSeconds = ($elapsedSeconds / $this->progress) * 100;
            $remainingSeconds = $estimatedTotalSeconds - $elapsedSeconds;

            if ($remainingSeconds < 60) {
                return round($remainingSeconds) . 's';
            } elseif ($remainingSeconds < 3600) {
                return round($remainingSeconds / 60) . 'm';
            } else {
                return round($remainingSeconds / 3600, 1) . 'h';
            }
        }

        return null;
    }

    /**
     * Scope to pending jobs.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to processing jobs.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope to completed jobs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to failed jobs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to jobs for a specific workspace.
     */
    public function scopeForWorkspace($query, Workspace $workspace)
    {
        return $query->whereHas('layer', function ($q) use ($workspace) {
            $q->where('workspace_id', $workspace->id);
        });
    }
}
