<?php

namespace Kakaprodo\PresignedAction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** A short-lived, single-use authentication code shared between applications. */
class TempAuthCode extends Model
{
    protected $fillable = [
        'uuid',
        'state_key',
        'subscriber_id',
        'subscriber_type',
        'metadata',
        'expires_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime'
    ];

    /** Return the configured table used for temporary authentication codes. */
    public function getTable(): string
    {
        return (string) config('presigned-action.temp_auth_code.table', 'temp_auth_codes');
    }

    /** Return the subscriber model associated with this authentication code. */
    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    /** Determine whether the code can still be used. */
    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? true;
    }
}
