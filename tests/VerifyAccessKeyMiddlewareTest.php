<?php

namespace Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Kakaprodo\PresignedAction\Middleware\VerifyAccessKeyMiddleware;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;
use Symfony\Component\HttpFoundation\Response;

class VerifyAccessKeyMiddlewareTest extends TestCase
{
    public function test_it_allows_a_valid_access_key_and_exposes_it_on_the_request(): void
    {
        $accessKey = $this->createAccessKey();
        $request = $this->requestFor($accessKey);

        $response = (new VerifyAccessKeyMiddleware())->handle(
            $request,
            fn() => new Response('ok')
        );

        $this->assertSame('ok', $response->getContent());
        $this->assertSame($accessKey->id, $request->temporaryAccessKey()->id);
    }

    public function test_it_rejects_a_request_without_the_temporary_access_key_header(): void
    {
        $request = Request::create('/');
        $request->headers->set('X-WHOMAI', 'staff-1');

        $this->expectException(PresignedActionException::class);
        $this->expectExceptionMessage('The X-TEMP-ACCESS-KEY is required');

        (new VerifyAccessKeyMiddleware())->handle($request, fn() => new Response());
    }

    public function test_it_rejects_a_request_with_the_wrong_identity(): void
    {
        $accessKey = $this->createAccessKey();
        $request = $this->requestFor($accessKey, 'different-staff');

        $this->expectExceptionMessage('Unauthorized - wrong identifier');

        (new VerifyAccessKeyMiddleware())->handle($request, fn() => new Response());
    }

    public function test_it_rejects_an_expired_access_key(): void
    {
        $accessKey = $this->createAccessKey(Carbon::yesterday());
        $request = $this->requestFor($accessKey);

        $this->expectExceptionMessage('Unauthorized - access key has expired');

        (new VerifyAccessKeyMiddleware())->handle($request, fn() => new Response());
    }

    private function createAccessKey(?Carbon $expiresAt = null): TemporaryAccessKey
    {
        return TemporaryAccessKey::create([
            'uuid' => 'key-123',
            'whoami' => 'staff-1',
            'expires_at' => $expiresAt ?? Carbon::tomorrow(),
            'accessible_id' => 1,
            'accessible_type' => AccessibleModel::class,
            'settings' => [],
            'scopes' => ['scope1', 'scope2'],
        ]);
    }

    private function requestFor(TemporaryAccessKey $accessKey, string $whoami = 'staff-1'): Request
    {
        $request = Request::create('/');
        $request->headers->set('X-WHOMAI', $whoami);
        $request->headers->set(
            'X-TEMP-ACCESS-KEY',
            encrypt("{$accessKey->uuid}-tempo-{$accessKey->accessible_id}::{$accessKey->accessible_type}")
        );

        return $request;
    }
}
