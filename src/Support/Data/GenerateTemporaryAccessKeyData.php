<?php

namespace Kakaprodo\PresignedAction\Support\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Kakaprodo\CustomData\CustomData;

/**
 * @property null|Model $accessible Model that owns the temporary access key
 * @property string $whoami Identifier of the requesting staff/user
 * @property null|Carbon $expires_at
 * @property array $scopes Scopes granted by the temporary access key
 * @property array $permissions Permissions granted by the temporary access key
 * @property null|string $origin Origin of the temporary access key
 * @property bool $is_independent Whether the temporary access key is independent (not tied to a specific model)
 * 
 */
class GenerateTemporaryAccessKeyData extends CustomData
{
    protected function expectedProperties(): array
    {
        return [
            'accessible?' => $this->property(Model::class),
            'whoami' => $this->property()->string()->rules(['required', 'string', 'max:255']),
            'expires_at?' => $this->property(Carbon::class),
            'settings?' => $this->property()->array([]),
            'scopes?' => $this->property()->array([]),
            'permissions?' => $this->property()->array([]),
            'origin?' => $this->property()->string(),
            'is_independent?' => $this->property()->bool(false),
        ];
    }

    public function ignoreForKeyGenerator(): array
    {
        return [
            'accessible',
            'expires_at',
            'is_independent'
        ];
    }
}
