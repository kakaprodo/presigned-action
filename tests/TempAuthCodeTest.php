<?php

namespace Tests;

use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Kakaprodo\PresignedAction\Facades\PresignedAction;
use Kakaprodo\PresignedAction\Models\TempAuthCode;

class TempAuthCodeTest extends TestCase
{
    public function test_it_generates_a_code_with_a_unique_state_and_twenty_second_expiration(): void
    {
        $code = PresignedAction::generateTempAuthCode(['state_key' => 'kitchen-state-1']);

        $this->assertInstanceOf(TempAuthCode::class, $code);
        $this->assertSame('kitchen-state-1', $code->state_key);
        $this->assertNotEmpty($code->uuid);
        $this->assertTrue($code->expires_at->between(now()->addSeconds(19), now()->addSeconds(21)));
    }

    public function test_it_rejects_a_duplicate_state_key(): void
    {
        PresignedAction::generateTempAuthCode(['state_key' => 'duplicate-state']);

        $this->expectException(PresignedActionException::class);
        $this->expectExceptionCode(409);

        PresignedAction::generateTempAuthCode(['state_key' => 'duplicate-state']);
    }

    public function test_it_associates_metadata_with_a_scanned_code(): void
    {
        $code = PresignedAction::generateTempAuthCode(['state_key' => 'state-1']);

        $updated = PresignedAction::associateTempAuthCode([
            'uuid' => $code->uuid,
            'subscriber_id' => 'subscriber-1',
            'subscriber_type' => 'subscriber',
            'metadata' => ['access_token' => 'token-1'],
        ]);

        $this->assertSame('subscriber-1', $updated->subscriber_id);
        $this->assertSame('subscriber', $updated->subscriber_type);
        $this->assertSame(['access_token' => 'token-1'], $updated->metadata);
    }

    public function test_it_merges_generation_metadata_with_subscriber_metadata(): void
    {
        $code = PresignedAction::generateTempAuthCode([
            'state_key' => 'metadata-merge-state',
            'metadata' => [
                'origin' => 'kitchen',
                'shared' => 'generation',
            ],
        ]);

        $updated = PresignedAction::associateTempAuthCode([
            'uuid' => $code->uuid,
            'subscriber_type' => 'subscriber',
            'metadata' => [
                'shared' => 'subscriber',
                'subscriber_id' => 'subscriber-1',
            ],
        ]);

        $this->assertSame([
            'origin' => 'kitchen',
            'shared' => 'subscriber',
            'subscriber_id' => 'subscriber-1',
        ], $updated->metadata);
    }

    public function test_fetching_a_pending_code_does_not_consume_it(): void
    {
        $code = PresignedAction::generateTempAuthCode(['state_key' => 'state-pending']);

        $result = PresignedAction::fetchTempAuthCode('state-pending', $code->uuid);

        $this->assertSame('pending', $result['status']);
        $this->assertTrue(TempAuthCode::query()->where('uuid', $code->uuid)->exists());
    }

    public function test_fetching_a_completed_code_returns_metadata_and_consumes_it(): void
    {
        $code = PresignedAction::generateTempAuthCode(['state_key' => 'state-complete']);
        PresignedAction::associateTempAuthCode([
            'uuid' => $code->uuid,
            'subscriber_type' => 'subscriber',
            'metadata' => ['subscriber_payload' => 'value'],
        ]);

        $result = PresignedAction::fetchTempAuthCode('state-complete', $code->uuid);

        $this->assertSame('completed', $result['status']);
        $this->assertSame(['subscriber_payload' => 'value'], $result['metadata']);
        $this->assertFalse(TempAuthCode::query()->where('uuid', $code->uuid)->exists());
    }

    public function test_fetching_with_the_wrong_state_key_fails(): void
    {
        $code = PresignedAction::generateTempAuthCode(['state_key' => 'correct-state']);

        $this->expectException(PresignedActionException::class);
        $this->expectExceptionCode(404);

        PresignedAction::fetchTempAuthCode('wrong-state', $code->uuid);
    }

    public function test_expired_codes_cannot_be_associated_or_fetched(): void
    {
        $code = PresignedAction::generateTempAuthCode(['state_key' => 'expired-state']);
        $code->update(['expires_at' => now()->subSecond()]);

        $this->expectException(PresignedActionException::class);
        $this->expectExceptionCode(410);

        PresignedAction::associateTempAuthCode([
            'uuid' => $code->uuid,
            'subscriber_type' => 'subscriber',
        ]);
    }
}
