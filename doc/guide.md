## Presigned Action

0. Config content
   should have :

- should_run_migration: boolean, based on this you should load the migration in the service provider
- key_expires_after: `period in minutes`

## Maintenance

Expired keys can be removed with `php artisan presigned-action:purge-expired`.
Only non-expired keys are considered for reuse during generation.
