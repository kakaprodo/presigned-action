# Presigned Action

A Laravel package for securely presigning actions on any entity, enabling controlled access and authorization without requiring users to share their passwords.

```php
use Kakaprodo\PresignedAction\Facades\PresignedAction;

$accessKey = PresignedAction::generateAccessKey([
	'accessible' => $order,
	'whoami' => 'partner-42',
    'expires_at' => now()->addHour(),
	'scopes' => ['orders.read', 'orders.download'],
	'settings' => [
		'source' => 'partner-api',
	],
]);

return response()->json($accessKey->formatPublicTempKey());
```

And here is the output

```json
{
    "temp_access_key": "eyJpdiI6IndsK3FUUmtQN.....0MzZkZiIsInRhZyI6IiJ9",
    "expires_at": 1789009228
}
```

**Why Presigned Action?**

In a microservice architecture, a user may be authenticated and authorized by a Core service while the entity she/he needs to access belongs to another service.

For example, a user in a Core service may be authorized to perform an action on a Shop managed by another service. The user should be able to perform that action without having a user account in the Shop service.

Presigned Action provides a way to authorize this cross-service access without duplicating users, roles, or permissions across services.

[Official Doc On Yupidoc](https://yupidoc.com/docs/presigned-action)
