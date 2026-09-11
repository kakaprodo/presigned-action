<?php

namespace Tests;

use Illuminate\Http\Request;
use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Kakaprodo\PresignedAction\Middleware\PresignedActionOriginMiddleware;
use Kakaprodo\PresignedAction\Middleware\PresignedActionPermissionMiddleware;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;
use Symfony\Component\HttpFoundation\Response;

class PresignedActionOriginAndPermissionMiddlewareTest extends TestCase
{
    public function test_origin_and_permission_middleware_allow_matching_key(): void
    {
        $this->setAccessKey('partner-api', ['orders.view']);
        $next = fn() => new Response('ok');

        $this->assertSame('ok', (new PresignedActionOriginMiddleware())->handle(Request::create('/'), $next, 'partner-api')->getContent());
        $this->assertSame('ok', (new PresignedActionPermissionMiddleware())->handle(Request::create('/'), $next, 'orders.view')->getContent());
    }

    public function test_origin_middleware_rejects_a_different_origin(): void
    {
        $this->setAccessKey('partner-api', []);
        $this->expectException(PresignedActionException::class);

        (new PresignedActionOriginMiddleware())->handle(Request::create('/'), fn() => new Response(), 'internal-api');
    }

    public function test_permission_middleware_rejects_missing_permission(): void
    {
        $this->setAccessKey('partner-api', ['orders.view']);
        $this->expectExceptionMessage('Unauthorized - insufficient permission');

        (new PresignedActionPermissionMiddleware())->handle(Request::create('/'), fn() => new Response(), 'orders.update');
    }

    private function setAccessKey(string $origin, array $permissions): void
    {
        $accessKey = TemporaryAccessKey::create([
            'uuid' => 'key-'.uniqid(),
            'whoami' => 'staff-1',
            'expires_at' => now()->addHour(),
            'accessible_id' => 1,
            'accessible_type' => AccessibleModel::class,
            'origin' => $origin,
            'permissions' => $permissions,
        ]);

        Request::macro('temporaryAccessKey', fn() => $accessKey);
        app()->instance('request', Request::create('/'));
    }
}
