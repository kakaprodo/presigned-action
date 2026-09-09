<?php

namespace Kakaprodo\PresignedAction\Support\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Kakaprodo\CustomData\CustomData;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;

/**
 * @property Model $accessible Model that owns the temporary access key
 * @property string $whoami Identifier of the requesting staff/user
 * @property null|Carbon $expires_at
 * @property array $scopes Scopes granted by the temporary access key
 * @property array $permissions Permissions granted by the temporary access key
 * @property null|string $origin Origin of the temporary access key
 * 
 */
class GenerateTemporaryAccessKeyData extends CustomData
{
    protected function expectedProperties(): array
    {
        return [
            'accessible' => $this->property(Model::class),
            'whoami' => $this->property()->string()->rules(['required', 'string', 'max:255']),
            'expires_at?' => $this->property(Carbon::class),
            'settings?' => $this->property()->array([]),
            'scopes?' => $this->property()->array([]),
            'permissions?' => $this->property()->array([]),
            'origin?' => $this->property()->string()
        ];
    }

    public function accessKeyIsGeneratedWithSameValues(?TemporaryAccessKey $existingAccessKey): bool
    {
        if (! $existingAccessKey) {
            return false;
        }

        return $this->origin === $existingAccessKey->origin
            && $this->sameValues($this->scopes ?? [], $existingAccessKey->scopes ?? [])
            && $this->sameValues($this->permissions ?? [], $existingAccessKey->permissions ?? []);
    }

    private function sameValues(array $first, array $second): bool
    {
        $normalize = static function (array $values): array {
            $values = array_map(static fn($value) => (string) $value, $values);
            $values = array_values(array_unique($values));
            sort($values);

            return $values;
        };

        return $normalize($first) === $normalize($second);
    }
}
