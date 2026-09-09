<?php

namespace Kakaprodo\PresignedAction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Kakaprodo\PresignedAction\Models\Traits\HasFieldSettings;

/**
 * Temporary access key owned by a polymorphic accessible model.
 */
class TemporaryAccessKey extends Model
{
    use HasFactory, HasFieldSettings;

    protected $fillable = [
        'uuid',
        'whoami',
        'expires_at',
        'accessible_id',
        'accessible_type',
        'settings',
        'scopes',
        'permissions',
        'origin',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'settings' => 'array',
        'scopes' => 'array',
        'permissions' => 'array'
    ];

    public function accessible(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Verify that the key was issued for the given identity.
     * */
    public function revalidateWhoami(mixed $whoami): bool
    {
        return $this->whoami === $whoami;
    }

    /**
     * Check if a given accessible model matches with the one set  */
    public function revalidateAccessible(Model $accessible): bool
    {
        return (string) $this->accessible_id === (string) $accessible->getKey()
            && $this->accessible_type === get_class($accessible);
    }

    /** Determine whether at least one requested scope is granted. */
    public function hasScope(array $scopes): bool
    {
        return collect($scopes)->intersect($this->scopes ?? [])->isNotEmpty();
    }

    /** Determine whether at least one requested permission is granted. */
    public function hasPermission(array $permissions): bool
    {
        return collect($permissions)->intersect($this->permissions ?? [])->isNotEmpty();
    }

    /**
     * Return the encrypted public key used to authenticate the accessible model.
     */
    public function formatPublicTempKey(): array
    {
        return [
            'temp_access_key' => encrypt("{$this->uuid}-tempo-{$this->accessible_id}::{$this->accessible_type}"),
            'expires_at' => $this->expires_at->timestamp
        ];
    }
}
