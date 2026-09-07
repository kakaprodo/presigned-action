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
        $this->assertSame($accessible->getKey(), $accessKey->accessible_id);
        $this->assertSame(AccessibleModel::class, $accessKey->accessible_type);
        $this->assertTrue($accessKey->expires_at->isFuture());
    }
}

class AccessibleModel extends Model
{
    protected $table = 'accessible_models';

    protected $guarded = [];
}
