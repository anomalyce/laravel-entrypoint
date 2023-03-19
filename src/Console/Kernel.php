<?php

namespace Anomalyce\LaravelEntrypoint\Console;

use App\Console\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
  /**
   * Register the commands for the application.
   */
  protected function commands(): void
  {
    if (is_dir($commandsPath = __DIR__.'/Commands')) {
      $this->load($commandsPath);
    }

    if (file_exists($consoleRoutes = base_path('routes/console.php'))) {
      require $consoleRoutes;
    }
  }
}
