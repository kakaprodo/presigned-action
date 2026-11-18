<?php

namespace Kakaprodo\PresignedAction\Support\Data;

/**
 * Validated subscriber data associated with a scanned authentication code.
 *
 * @property string $uuid Scanned temporary authentication-code UUID.
 * @property string|null $subscriber_id Optional subscriber identifier.
 * @property string $subscriber_type Subscriber mode or type.
 * @property array<string, mixed> $metadata Application-specific subscriber metadata.
 */
class AssociateTempAuthCodeData extends TempAuthCodeData
{
    /** @return array<string, mixed> */
    protected function expectedProperties(): array
    {
        return [
            'uuid' => $this->property()->string()->rules(['required', 'string', 'max:255']),
            'subscriber_id?' => $this->property()->string()->rules(['nullable', 'string', 'max:255']),
            'subscriber_type' => $this->property()->string()->rules(['required', 'string', 'max:255']),
            'metadata?' => $this->property()->array([]),
        ];
    }

    /** Validate that the scanned code exists and has not expired. */
    public function boot(): void
    {
        $code = $this->findByUuid($this->uuid);

        if (! $code) {
            $this->throwCodeNotFound();
        }

        if ($code->isExpired()) {
            $this->throwCodeExpired();
        }
    }
}
