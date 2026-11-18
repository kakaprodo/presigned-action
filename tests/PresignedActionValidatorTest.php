<?php

namespace Tests;

use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Kakaprodo\PresignedAction\Facades\PresignedAction;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;

class PresignedActionValidatorTest extends TestCase
{
    protected function tearDown(): void
    {
        PresignedAction::validator()->flush();
        parent::tearDown();
    }

    public function test_it_defines_and_executes_named_and_group_validators(): void
    {
        $key = TemporaryAccessKey::create([
            'uuid' => 'validator-key', 'whoami' => 'staff-1', 'expires_at' => now()->addHour(),
            'accessible_id' => 1, 'accessible_type' => AccessibleModel::class,
        ]);
        \Illuminate\Http\Request::macro('temporaryAccessKey', fn() => $key);
        app()->instance('request', \Illuminate\Http\Request::create('/'));

        PresignedAction::validator()->register('orders', [
            'owns' => fn(TemporaryAccessKey $accessKey, string $orderId) => $accessKey->whoami.'-'.$orderId,
        ]);
        PresignedAction::validator()->register('simple', fn(TemporaryAccessKey $accessKey, string $value) => $accessKey->whoami.'-'.$value);
        PresignedAction::validator()->register('nested', [
            'orders' => [
                'owns' => fn(TemporaryAccessKey $accessKey, string $orderId) => $accessKey->whoami.'-'.$orderId,
            ],
        ]);

        $this->assertSame('staff-1-42', PresignedAction::validator()->check('orders.owns', ['42']));
        $this->assertSame('staff-1-ok', PresignedAction::validator()->check('simple', ['ok']));
        $this->assertSame('staff-1-43', PresignedAction::validator()->check('nested.orders.owns', ['43']));
    }

    public function test_it_throws_the_custom_error_message_when_validation_fails(): void
    {
        $this->setCurrentAccessKey();
        PresignedAction::validator()->register('orders', fn() => false);

        $this->expectException(PresignedActionException::class);
        $this->expectExceptionMessage('The order is not accessible.');

        PresignedAction::validator()->check('orders', [], 'The order is not accessible.');
    }

    public function test_it_uses_the_default_error_message_when_validation_fails(): void
    {
        $this->setCurrentAccessKey();
        PresignedAction::validator()->register('orders', fn() => false);

        $this->expectException(PresignedActionException::class);
        $this->expectExceptionMessage('Permission denied');

        PresignedAction::validator()->check('orders');
    }

    private function setCurrentAccessKey(): void
    {
        $key = TemporaryAccessKey::create([
            'uuid' => 'failed-validator-key-'.uniqid(),
            'whoami' => 'staff-1',
            'expires_at' => now()->addHour(),
            'accessible_id' => 1,
            'accessible_type' => AccessibleModel::class,
        ]);

        \Illuminate\Http\Request::macro('temporaryAccessKey', fn() => $key);
        app()->instance('request', \Illuminate\Http\Request::create('/'));
    }
}
