<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get all volumes uploaded by this user.
     */
    public function volumes(): HasMany
    {
        return $this->hasMany(Volume::class, 'uploaded_by');
    }

    /**
     * Get all workspaces created by this user.
     */
    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'created_by');
    }

    /**
     * Get recently accessed workspaces.
     */
    public function recentWorkspaces(int $limit = 5): HasMany
    {
        return $this->workspaces()
            ->whereNotNull('last_accessed_at')
            ->orderBy('last_accessed_at', 'desc')
            ->limit($limit);
    }

    /**
     * Get total storage used by uploaded volumes in bytes.
     */
    public function getTotalStorageUsedAttribute(): int
    {
        return $this->volumes()->sum('file_size');
    }

    /**
     * Get human-readable total storage used.
     */
    public function getTotalStorageUsedHumanAttribute(): string
    {
        $bytes = $this->total_storage_used;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
