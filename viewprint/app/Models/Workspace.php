<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents an analysis workspace that combines volumes and eye-tracking data.
 *
 * @property int $id
 * @property string $name User-defined workspace name
 * @property string|null $description Optional detailed description
 * @property int $created_by User who created the workspace
 * @property array|null $settings Workspace preferences
 * @property \Carbon\Carbon|null $last_accessed_at When the workspace was last opened
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\WorkspaceLayer> $layers
 */
class Workspace extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description',
        'created_by',
        'settings',
        'last_accessed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'settings' => '{}',
    ];

    /**
     * Get the user who created this workspace.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all layers in this workspace.
     */
    public function layers(): HasMany
    {
        return $this->hasMany(WorkspaceLayer::class)->orderBy('position');
    }

    /**
     * Get only visible layers.
     */
    public function visibleLayers(): HasMany
    {
        return $this->layers()->where('visible', true);
    }

    /**
     * Get layers by type.
     */
    public function layersByType(string $type): HasMany
    {
        return $this->layers()->where('layer_type', $type);
    }

    /**
     * Get base volume layers.
     */
    public function baseVolumeLayers(): HasMany
    {
        return $this->layersByType('base_volume');
    }

    /**
     * Get participant volume layers.
     */
    public function participantVolumeLayers(): HasMany
    {
        return $this->layersByType('participant_volume');
    }

    /**
     * Get calculated layers.
     */
    public function calculatedLayers(): HasMany
    {
        return $this->layersByType('calculated');
    }

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a setting value.
     */
    public function setSetting(string $key, $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get default colormap from settings.
     */
    public function getDefaultColormapAttribute(): string
    {
        return $this->getSetting('default_colormap', 'gray');
    }

    /**
     * Check if crosshair is visible.
     */
    public function getCrosshairVisibleAttribute(): bool
    {
        return $this->getSetting('crosshair_visible', true);
    }

    /**
     * Get auto-save interval in seconds.
     */
    public function getAutoSaveIntervalAttribute(): int
    {
        return $this->getSetting('auto_save_interval_seconds', 30);
    }

    /**
     * Touch the last accessed timestamp.
     */
    public function touchLastAccessed(): self
    {
        $this->last_accessed_at = now();
        $this->save();

        return $this;
    }

    /**
     * Duplicate the workspace with all its layers.
     */
    public function duplicate(string $newName): Workspace
    {
        $newWorkspace = $this->replicate();
        $newWorkspace->name = $newName;
        $newWorkspace->last_accessed_at = null;
        $newWorkspace->save();

        // Duplicate all layers
        $this->layers->each(function ($layer) use ($newWorkspace) {
            $newLayer = $layer->replicate();
            $newLayer->workspace_id = $newWorkspace->id;
            $newLayer->save();
        });

        return $newWorkspace;
    }

    /**
     * Get the next available layer position.
     */
    public function getNextLayerPosition(): int
    {
        return $this->layers()->max('position') + 1 ?? 0;
    }

    /**
     * Reorder layers to ensure continuous positions.
     */
    public function reorderLayers(): void
    {
        $this->layers()
            ->orderBy('position')
            ->get()
            ->each(function ($layer, $index) {
                $layer->update(['position' => $index]);
            });
    }

    /**
     * Export workspace configuration as JSON.
     */
    public function export(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'settings' => $this->settings,
            'layers' => $this->layers->map(function ($layer) {
                return [
                    'type' => $layer->layer_type,
                    'name' => $layer->name,
                    'position' => $layer->position,
                    'visible' => $layer->visible,
                    'opacity' => $layer->opacity,
                    'configuration' => $layer->configuration,
                ];
            })->toArray(),
        ];
    }

    /**
     * Scope to workspaces created by a specific user.
     */
    public function scopeCreatedBy($query, User $user)
    {
        return $query->where('created_by', $user->id);
    }

    /**
     * Scope to recently accessed workspaces.
     */
    public function scopeRecentlyAccessed($query, int $limit = 10)
    {
        return $query->whereNotNull('last_accessed_at')
            ->orderBy('last_accessed_at', 'desc')
            ->limit($limit);
    }
}
