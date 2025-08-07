<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Represents a visualization layer within a workspace.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $layer_type One of: base_volume, participant_volume, calculated, external
 * @property string $name Display name for the layer
 * @property int $position Rendering order (0 = bottom)
 * @property bool $visible Whether the layer is currently visible
 * @property int $opacity Layer opacity (0-100)
 * @property array $configuration Layer-specific settings
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\Workspace $workspace
 * @property-read \App\Models\CalculationJob|null $calculationJob
 */
class WorkspaceLayer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'workspace_id',
        'layer_type',
        'name',
        'position',
        'visible',
        'opacity',
        'configuration',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'configuration' => 'array',
        'visible' => 'boolean',
        'opacity' => 'integer',
        'position' => 'integer',
    ];

    /**
     * Layer type constants.
     */
    const TYPE_BASE_VOLUME = 'base_volume';
    const TYPE_PARTICIPANT_VOLUME = 'participant_volume';
    const TYPE_CALCULATED = 'calculated';
    const TYPE_EXTERNAL = 'external';

    /**
     * Get the workspace this layer belongs to.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the calculation job for this layer (if it's a calculated layer).
     */
    public function calculationJob(): HasOne
    {
        return $this->hasOne(CalculationJob::class);
    }

    /**
     * Get configuration value.
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->configuration, $key, $default);
    }

    /**
     * Set configuration value.
     */
    public function setConfig(string $key, $value): self
    {
        $config = $this->configuration;
        data_set($config, $key, $value);
        $this->configuration = $config;

        return $this;
    }

    /**
     * Check if this is a base volume layer.
     */
    public function getIsBaseVolumeAttribute(): bool
    {
        return $this->layer_type === self::TYPE_BASE_VOLUME;
    }

    /**
     * Check if this is a participant volume layer.
     */
    public function getIsParticipantVolumeAttribute(): bool
    {
        return $this->layer_type === self::TYPE_PARTICIPANT_VOLUME;
    }

    /**
     * Check if this is a calculated layer.
     */
    public function getIsCalculatedAttribute(): bool
    {
        return $this->layer_type === self::TYPE_CALCULATED;
    }

    /**
     * Get the volume ID for base volume layers.
     */
    public function getVolumeIdAttribute(): ?int
    {
        if ($this->is_base_volume) {
            return $this->getConfig('volume_id');
        }
        return null;
    }

    /**
     * Get the volume model for base volume layers.
     */
    public function getVolumeAttribute(): ?Volume
    {
        if ($this->volume_id) {
            return Volume::find($this->volume_id);
        }
        return null;
    }

    /**
     * Get session IDs for participant volume layers.
     */
    public function getSessionIdsAttribute(): array
    {
        if ($this->is_participant_volume) {
            return $this->getConfig('session_ids', []);
        }
        return [];
    }

    /**
     * Get eye-tracking sessions for participant volume layers.
     */
    public function getSessionsAttribute()
    {
        if (!empty($this->session_ids)) {
            return EyeTrackingSession::whereIn('id', $this->session_ids)->get();
        }
        return collect();
    }

    /**
     * Get the colormap for this layer.
     */
    public function getColormapAttribute(): string
    {
        return $this->getConfig('colormap', 'gray');
    }

    /**
     * Get the NIfTI file URL for this layer.
     */
    public function getNiftiUrlAttribute(): ?string
    {
        // For base volumes
        if ($this->is_base_volume && $this->volume) {
            return $this->volume->url;
        }

        // For participant volumes
        if ($this->is_participant_volume) {
            return $this->getConfig('nifti_url');
        }

        // For calculated layers
        if ($this->is_calculated) {
            return $this->getConfig('cached_result_path');
        }

        return null;
    }

    /**
     * Get visualization type for participant volume layers.
     */
    public function getVisualizationTypeAttribute(): ?string
    {
        if ($this->is_participant_volume) {
            return $this->getConfig('visualization_type', 'heatmap');
        }
        return null;
    }

    /**
     * Get calculation type for calculated layers.
     */
    public function getCalculationTypeAttribute(): ?string
    {
        if ($this->is_calculated) {
            return $this->getConfig('calculation_type');
        }
        return null;
    }

    /**
     * Toggle visibility.
     */
    public function toggleVisibility(): self
    {
        $this->visible = !$this->visible;
        $this->save();

        return $this;
    }

    /**
     * Move layer up in the stack.
     */
    public function moveUp(): self
    {
        $swapWith = $this->workspace->layers()
            ->where('position', '<', $this->position)
            ->orderBy('position', 'desc')
            ->first();

        if ($swapWith) {
            $tempPosition = $this->position;
            $this->position = $swapWith->position;
            $swapWith->position = $tempPosition;

            $this->save();
            $swapWith->save();
        }

        return $this;
    }

    /**
     * Move layer down in the stack.
     */
    public function moveDown(): self
    {
        $swapWith = $this->workspace->layers()
            ->where('position', '>', $this->position)
            ->orderBy('position', 'asc')
            ->first();

        if ($swapWith) {
            $tempPosition = $this->position;
            $this->position = $swapWith->position;
            $swapWith->position = $tempPosition;

            $this->save();
            $swapWith->save();
        }

        return $this;
    }

    /**
     * Move layer to specific position.
     */
    public function moveToPosition(int $newPosition): self
    {
        $oldPosition = $this->position;

        if ($newPosition === $oldPosition) {
            return $this;
        }

        // Shift other layers
        if ($newPosition < $oldPosition) {
            // Moving up - shift others down
            $this->workspace->layers()
                ->whereBetween('position', [$newPosition, $oldPosition - 1])
                ->increment('position');
        } else {
            // Moving down - shift others up
            $this->workspace->layers()
                ->whereBetween('position', [$oldPosition + 1, $newPosition])
                ->decrement('position');
        }

        $this->position = $newPosition;
        $this->save();

        return $this;
    }

    /**
     * Scope to visible layers only.
     */
    public function scopeVisible($query)
    {
        return $query->where('visible', true);
    }

    /**
     * Scope to layers of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('layer_type', $type);
    }
}
