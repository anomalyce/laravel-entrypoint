<?php

namespace Anomalyce\LaravelEntrypoint;

use Illuminate\Support\Env;
use Illuminate\Foundation\Application as Laravel;
use Illuminate\Contracts\Foundation\CachesRoutes;

class Entrypoint
{
  /**
   * Holds the configuration path.
   *
   * @var string|null
   */
  protected ?string $config = null;

  /**
   * Holds the locale path.
   *
   * @var string|null
   */
  protected ?string $locale = null;

  /**
   * Holds the routes path.
   *
   * @var string|null
   */
  protected ?string $routes = null;

  /**
   * Holds the storage path.
   *
   * @var string|null
   */
  protected ?string $storage = null;

  /**
   * Holds the database path.
   *
   * @var string|null
   */
  protected ?string $database = null;

  /**
   * Holds the bootstrap path.
   *
   * @var string|null
   */
  protected ?string $bootstrap = null;

  /**
   * Holds the public path.
   *
   * @var string|null
   */
  protected ?string $public = null;

  /**
   * Instantiate a new Laravel entrypoint object.
   *
   * @param  string  $basePath
   * @return void
   */
  public function __construct(protected string $basePath)
  {
    //
  }

  /**
   * Specify the path that holds configuration files.
   *
   * @param  string  $path
   * @return void
   */
  public function loadConfigurationFrom(string $path): void
  {
    $this->config = $path;
  }

  /**
   * Specify the path that holds locale files.
   *
   * @param  string  $path
   * @return void
   */
  public function loadLocaleFrom(string $path): void
  {
    $this->locale = $path;
  }

  /**
   * Specify the path that holds route files.
   *
   * @param  string  $path
   * @return void
   */
  public function loadRoutesFrom(string $path): void
  {
    $this->routes = $path;
  }

  /**
   * Specify the path that holds all of the storage files.
   *
   * @param  string  $path
   * @return void
   */
  public function loadStorageFrom(string $path): void
  {
    $this->storage = $path;
  }

  /**
   * Specify the path that holds all of the database files.
   *
   * @param  string  $path
   * @return void
   */
  public function loadDatabaseFrom(string $path): void
  {
    $this->database = $path;
  }

  /**
   * Specify the path that holds all of the bootstrap cache.
   *
   * @param  string  $path
   * @return void
   */
  public function storeBootstrapCacheIn(string $path): void
  {
    $this->bootstrap = $path;
  }

  /**
   * Specify the public path.
   *
   * @param  string  $path
   * @return void
   */
  public function serveFrom(string $path): void
  {
    $this->public = $path;
  }

  /**
   * Serve the application.
   *
   * @param  \Illuminate\Foundation\Application  $app
   * @return void
   */
  public function serve(Laravel $app): void
  {
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

    $response = $kernel->handle(
     $request = \Illuminate\Http\Request::capture()
    )->send();

    $kernel->terminate($request, $response);
  }

  /**
   * Serve the application for CLI usage.
   *
   * @param  \Illuminate\Foundation\Application  $app
   * @return integer
   */
  public function console(Laravel $app): int
  {
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

    $status = $kernel->handle(
      $input = new \Symfony\Component\Console\Input\ArgvInput,
      new \Symfony\Component\Console\Output\ConsoleOutput
    );

    $kernel->terminate($input, $status);

    return $status;
  }

  /**
   * Create the Laravel application.
   *
   * @param  string|null  $httpKernel  null
   * @param  string|null  $consoleKernel  null
   * @param  string|null  $exceptionHandler  null
   * @return \Illuminate\Foundation\Application
   */
  public function createApplication(?string $httpKernel = null, ?string $consoleKernel = null, ?string $exceptionHandler = null): Laravel
  {
    $app = new Laravel($this->basePath);

    $this->setApplicationPaths($app);
    $this->setCacheEnvValues($app);

    $app->singleton(
      \Illuminate\Contracts\Http\Kernel::class,
      $httpKernel ?? \App\Http\Kernel::class
    );

    $app->singleton(
      \Illuminate\Contracts\Console\Kernel::class,
      $consoleKernel ?? Console\Kernel::class
    );

    $app->singleton(
      \Illuminate\Contracts\Debug\ExceptionHandler::class,
      $exceptionHandler ?? \App\Exceptions\Handler::class
    );

    $entrypoint = $this;

    Laravel::macro('serve', fn () => $entrypoint->serve($this));
    Laravel::macro('console', fn () => $entrypoint->console($this));

    return $app;
  }

  /**
   * Sets the various application paths.
   *
   * @param  \Illuminate\Foundation\Application  $app
   * @return void
   */
  protected function setApplicationPaths(Laravel $app): void
  {
    if ($this->config) {
      $app->useConfigPath($this->normalizePath($this->config));
    }

    if ($this->locale) {
      $app->useLangPath($this->normalizePath($this->locale));
    }

    if ($this->storage) {
      $app->useStoragePath($this->normalizePath($this->storage));
    }

    if ($this->database) {
      $app->useDatabasePath($this->normalizePath($this->database));
    }

    if ($this->bootstrap) {
      $app->useBootstrapPath($this->normalizePath($this->bootstrap));
    }

    if ($this->public) {
      $app->usePublicPath($this->normalizePath($this->public));
    }

    $app->booted(function ($app) {
      if (! $this->routes) {
        return;
      }

      if ($app instanceof CachesRoutes and $app->routesAreCached()) {
        return;
      }

      $routes = glob($this->normalizePath($this->routes).'/*.php');

      foreach ((array) $routes as $route) {
        require $route;
      }
    });
  }

  /**
   * Sets the various bootstrap cache environment variable values.
   *
   * @param  \Illuminate\Foundation\Application  $app
   * @return void
   */
  protected function setCacheEnvValues(Laravel $app): void
  {
    $entries = [
      'APP_SERVICES_CACHE'  => $app->getCachedServicesPath(),
      'APP_PACKAGES_CACHE'  => $app->getCachedPackagesPath(),
      'APP_CONFIG_CACHE'    => $app->getCachedConfigPath(),
      'APP_ROUTES_CACHE'    => $app->getCachedRoutesPath(),
      'APP_EVENTS_CACHE'    => $app->getCachedEventsPath(),
    ];

    $repository = Env::getRepository();

    foreach ($entries as $env => $path) {
      if (! empty(Env::get($env))) {
        continue;
      }

      $relative = str_replace($app->bootstrapPath().'/cache/', '', $path);

      if ($path === $relative) {
        continue;
      }

      $repository->set($env, $app->bootstrapPath($relative));
    }
  }

  /**
   * Normalize a path.
   *
   * @param  string  $path
   * @return string
   */
  protected function normalizePath(string $path): string
  {
    if (substr($path, 0, 1) === DIRECTORY_SEPARATOR) {
      return $path;
    }

    return $this->basePath.DIRECTORY_SEPARATOR.$path;
  }
}
