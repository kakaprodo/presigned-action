<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Kakaprodo\PresignedAction\Facades\PresignedAction;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;

class PresignedActionTest extends TestCase
{
    public function test_it_generates_a_temporary_access_key_for_an_accessible_model(): void
    {
        $accessible = AccessibleModel::create();

        $accessKey = PresignedAction::generateAccessKey([
            'accessible' => $accessible,
            'whoami' => 'staff-1',
            'settings' => ['scope' => 'read'],
            'scopes' => ['scope1', 'scope2'],
        ]);

        $this->assertInstanceOf(TemporaryAccessKey::class, $accessKey);
        $this->assertSame('staff-1', $accessKey->whoami);
        $this->assertSame(['scope' => 'read'], $accessKey->settings);
        $this->assertSame(['scope1', 'scope2'], $accessKey->scopes);
        $this->assertSame([], $accessKey->permissions);
        $this->assertSame($accessible->getKey(), $accessKey->accessible_id);
        $this->assertSame(AccessibleModel::class, $accessKey->accessible_type);
        $this->assertTrue($accessKey->expires_at->isFuture());
    }

    public function test_it_persists_origin_and_permissions(): void
    {
        $accessible = AccessibleModel::create();

        $accessKey = PresignedAction::generateAccessKey([
            'accessible' => $accessible,
            'whoami' => 'staff-1',
            'origin' => 'partner-api',
            'scopes' => ['orders.read'],
            'permissions' => ['orders.view'],
        ]);

        $this->assertSame('partner-api', $accessKey->origin);
        $this->assertSame(['orders.view'], $accessKey->permissions);
    }

    public function test_it_reuses_only_an_unexpired_key_with_the_same_values(): void
    {
        $accessible = AccessibleModel::create();
        $options = [
            'accessible' => $accessible,
            'whoami' => 'staff-1',
            'origin' => 'partner-api',
            'scopes' => ['orders.read'],
            'permissions' => ['orders.view'],
        ];

        $first = PresignedAction::generateAccessKey($options);
        $same = PresignedAction::generateAccessKey($options);
        $different = PresignedAction::generateAccessKey([...$options, 'permissions' => ['orders.update']]);

        $this->assertSame($first->id, $same->id);
        $this->assertNotSame($first->id, $different->id);
        $this->assertSame(2, TemporaryAccessKey::query()->count());
    }

    public function test_it_creates_a_new_key_when_the_previous_matching_key_is_expired(): void
    {
        $accessible = AccessibleModel::create();
        $options = [
            'accessible' => $accessible,
            'whoami' => 'staff-1',
            'origin' => 'partner-api',
            'scopes' => ['orders.read'],
            'permissions' => ['orders.view'],
        ];

        $first = PresignedAction::generateAccessKey($options);
        $oldUuid = $first->uuid;
        $first->update(['expires_at' => now()->subMinute()]);
        $renewed = PresignedAction::generateAccessKey($options);

        $this->assertNotSame($first->id, $renewed->id);
        $this->assertNotSame($oldUuid, $renewed->uuid);
        $this->assertTrue($renewed->expires_at->isFuture());
        $this->assertSame(2, TemporaryAccessKey::query()->count());
    }

    public function test_access_key_revalidation_and_capability_helpers(): void
    {
        $accessible = AccessibleModel::create();
        $accessKey = TemporaryAccessKey::create([
            'uuid' => 'key-123',
            'whoami' => 'staff-1',
            'expires_at' => now()->addHour(),
            'accessible_id' => $accessible->getKey(),
            'accessible_type' => AccessibleModel::class,
            'scopes' => ['orders.read'],
            'permissions' => ['orders.view'],
        ]);

        $this->assertTrue($accessKey->revalidateAccessible($accessible));
        $this->assertTrue($accessKey->revalidateWhoami('staff-1'));
        $this->assertTrue($accessKey->hasScope(['orders.read', 'orders.write']));
        $this->assertTrue($accessKey->hasPermission(['orders.view']));
        $this->assertTrue($accessKey->can(['orders.view']));
        $this->assertFalse($accessKey->hasPermission(['orders.update']));
    }
}

class AccessibleModel extends Model
{
    protected $table = 'accessible_models';

    protected $guarded = [];
}
