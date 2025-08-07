<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Represents a NIfTI volume file with metadata.
 *
 * @property int $id
 * @property string $name User-friendly name
 * @property string|null $description Detailed description
 * @property string $file_path Path to NIfTI file in storage
 * @property int $file_size Size in bytes
 * @property array $dimensions [x, y, z] voxel dimensions
 * @property array $voxel_size [x, y, z] voxel size in mm
 * @property array $metadata Additional NIfTI header data
 * @property int $uploaded_by User who uploaded the volume
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\User $uploader
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\EyeTrackingSession> $eyeTrackingSessions
 */
class Volume extends Model
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
        'file_path',
        'file_size',
        'dimensions',
        'voxel_size',
        'metadata',
        'uploaded_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dimensions' => 'array',
        'voxel_size' => 'array',
        'metadata' => 'array',
        'file_size' => 'integer',
    ];

    /**
     * Get the user who uploaded this volume.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get all eye-tracking sessions for this volume.
     */
    public function eyeTrackingSessions(): HasMany
    {
        return $this->hasMany(EyeTrackingSession::class);
    }

    /**
     * Get the full storage path for the NIfTI file.
     */
    public function getFullPathAttribute(): string
    {
        return Storage::path($this->file_path);
    }

    /**
     * Get the public URL for the NIfTI file (if publicly accessible).
     */
    public function getUrlAttribute(): ?string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get human-readable file size.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get volume dimensions as a formatted string.
     */
    public function getDimensionsStringAttribute(): string
    {
        return implode(' × ', $this->dimensions);
    }

    /**
     * Get voxel size as a formatted string.
     */
    public function getVoxelSizeStringAttribute(): string
    {
        return implode(' × ', array_map(fn($size) => $size . 'mm', $this->voxel_size));
    }

    /**
     * Check if this volume is spatially compatible with another volume.
     */
    public function isCompatibleWith(Volume $other): bool
    {
        return $this->dimensions === $other->dimensions &&
            $this->voxel_size === $other->voxel_size &&
            ($this->metadata['coordinate_system'] ?? null) === ($other->metadata['coordinate_system'] ?? null);
    }

    /**
     * Delete the volume and its associated file.
     */
    protected static function booted(): void
    {
        static::deleting(function (Volume $volume) {
            // Delete the actual file when the model is deleted
            if (Storage::exists($volume->file_path)) {
                Storage::delete($volume->file_path);
            }
        });
    }
}
