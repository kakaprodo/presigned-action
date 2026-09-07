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
        'settings'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'settings' => 'array'
    ];

    public function accessible(): MorphTo
    {
        return $this->morphTo();
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
