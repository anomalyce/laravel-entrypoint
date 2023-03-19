# Laravel Entrypoint
A flexible way of structuring your Laravel applications.

### Defining the entrypoint
Create an `entrypoint.php` file at the root of your application.

```php
$entrypoint = new Anomalyce\LaravelEntrypoint\Entrypoint(__DIR__);

$entrypoint->loadConfigurationFrom('config');
$entrypoint->loadLocaleFrom('locale');
$entrypoint->loadRoutesFrom('routes');
$entrypoint->loadStorageFrom('storage');
$entrypoint->loadDatabaseFrom('storage/database');
$entrypoint->storeBootstrapCacheIn('storage/bootstrap');
$entrypoint->serveFrom('public');

return $entrypoint;
```

## Serving HTTP Applications
See `stubs/http.stub` for reference.

## Serving Console Applications
See `stubs/console.stub` for reference.
