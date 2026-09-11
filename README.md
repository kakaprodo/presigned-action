# Presigned Action

Presigned Action is a Laravel package for securely authorizing actions on an
Eloquent model without requiring the requesting person or service to have a
local user account.

It is useful when one service authenticates a user and another service owns the
resource being accessed.

## Installation

```bash
composer require kakaprodo/presigned-action
```

The package registers its service provider through Laravel package discovery.
By default, its migrations are loaded automatically. Publish the configuration
with:

```bash
php artisan vendor:publish --tag=config-presigned-action
```

## Generate an access key

Use the facade with an accessible Eloquent model:

```php
use Kakaprodo\PresignedAction\Facades\PresignedAction;

$accessKey = PresignedAction::generateAccessKey([
    'accessible' => $order,
    'whoami' => 'partner-42',
    'expires_at' => now()->addHour(),
    'origin' => 'partner-api',
    'scopes' => ['orders.read', 'orders.download'],
    'permissions' => ['orders.view'],
    'settings' => [
        'source' => 'partner-api',
    ],
]);

return response()->json($accessKey->formatPublicTempKey());
```

The generation options are:

| Option        | Required | Description                                                                 |
| ------------- | -------- | --------------------------------------------------------------------------- |
| `accessible`  | Yes      | Eloquent model the key grants access to.                                    |
| `whoami`      | Yes      | Identifier of the person or service receiving access.                       |
| `expires_at`  | No       | A Carbon expiration time. Defaults to `key_expires_after` minutes from now. |
| `origin`      | No       | Identifies the system or integration that generated the key.                |
| `scopes`      | No       | Array of scope names granted to the key.                                    |
| `permissions` | No       | Array of permission names granted to the key.                               |
| `settings`    | No       | Additional application-specific JSON data.                                  |

The public response contains an encrypted key and its expiration timestamp:

```json
{
    "temp_access_key": "eyJpdiI6IndsK3FUUmtQN.....0MzZkZiIsInRhZyI6IiJ9",
    "expires_at": 1789009228
}
```

## Key reuse and renewal

When generating a key, the package looks for an existing non-expired key with
the same accessible model, `whoami`, and `origin`. It reuses that key only
when its `scopes` and `permissions` also match.

- An unexpired key with matching values is returned unchanged.
- An expired key is ignored; a new key is created.
- A key with different scopes, permissions, or origin produces a new record.

Scopes and permissions are treated as unordered values when comparing keys.

## Validate incoming requests

Apply `VerifyAccessKeyMiddleware` to routes that require a valid access key:

```php
use Kakaprodo\PresignedAction\Middleware\VerifyAccessKeyMiddleware;

Route::middleware(VerifyAccessKeyMiddleware::class)
    ->get('/orders/{order}', ...);
```

The middleware expects these headers by default:

```text
X-TEMP-ACCESS-KEY: <encrypted temp_access_key>
X-WHOMAI: partner-42
```

The verified model is available from the facade or the request:

```php
$accessKey = PresignedAction::temporaryAccessKey();
// or
$accessKey = request()->temporaryAccessKey();
```

Configure different header names in `config/presigned-action.php` under
`access_key_validation`.

## Scopes and permissions

Use `PresignedActionScopeMiddleware` to require one or more scopes. Scope
arguments may be comma- or pipe-separated:

```php
Route::middleware([
    VerifyAccessKeyMiddleware::class,
    PresignedActionScopeMiddleware::class . ':orders.read,orders.download',
])->get('/orders/{order}/download', ...);
```

The middleware allows the request when at least one requested scope is granted.
The access key model also provides helpers for application-level checks:

```php
$accessKey->hasScope(['orders.read']);
$accessKey->hasPermission(['orders.view']);
$accessKey->can(['orders.view']); // shortcut for hasPermission()

$accessKey->revalidateAccissible($order);
$accessKey->revalidateWhoami('partner-42');
```

Register the middleware aliases in your application's HTTP kernel:

```php
protected $middlewareAliases = [
    'presigned-action.origin' => \Kakaprodo\PresignedAction\Middleware\PresignedActionOriginMiddleware::class,
    'presigned-action.permission' => \Kakaprodo\PresignedAction\Middleware\PresignedActionPermissionMiddleware::class,
    'presigned-action.scope' => \Kakaprodo\PresignedAction\Middleware\PresignedActionScopeMiddleware::class,
];
```

Then require an origin or permission on a route:

```php
Route::middleware([
    VerifyAccessKeyMiddleware::class,
    'presigned-action.origin:partner-api',
    'presigned-action.permission:orders.view',
])->get('/orders', ...);
```

## Artisan command

Generate a key interactively with:

```bash
php artisan presigned-action:generate partner-42 \
    --accessible-id=123 \
    --accessible-type="App\\Models\\Order" \
    --scopes=orders.read \
    --scopes=orders.download
```

Remove expired keys with:

```bash
php artisan presigned-action:purge-expired
```

## Custom validators

Validators can be registered from any service provider and are called with the
current temporary access key as their first argument:

```php
use Kakaprodo\PresignedAction\Facades\PresignedAction;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;

PresignedAction::validator()->register('orders', [
    'owns' => fn (TemporaryAccessKey $accessKey, $orderId) => $accessKey->whoami === $orderId,
]);

PresignedAction::validator()->register('simple', fn (TemporaryAccessKey $accessKey, $value) => $value !== null);

```

You can call a given validator using the `check` method:

```php
PresignedAction::validator()->check('orders.owns', [$orderId]);
PresignedAction::validator()->check('simple', [$value]);
```

If a validator returns `false`, `check()` throws a `PresignedActionException`
with `Permission denied` by default. Pass a third argument to customize the
error message:

```php
PresignedAction::validator()->check(
    'orders.owns',
    [$orderId],
    'This order is not accessible.',
);
```

## Configuration

```php
return [
    'should_run_migration' => true,
    'key_expires_after' => 3600,
    'access_key_validation' => [
        'whoami' => 'X-WHOMAI',
        'temp_access_key' => 'X-TEMP-ACCESS-KEY',
    ],
];
```

`key_expires_after` is expressed in minutes. The database migration creates the
`temporary_access_keys` table with nullable JSON `permissions` and nullable
string `origin` columns.

## Why Presigned Action?

In a microservice architecture, a user may be authenticated and authorized by
a core service while the entity they need to access belongs to another service.
Presigned Action provides controlled cross-service access without duplicating
users, roles, or permissions across services.

[Official documentation on Yupidoc](https://yupidoc.com/docs/presigned-action)
