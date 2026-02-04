<?php

namespace Iquesters\Dev;

use Illuminate\Support\ServiceProvider;
use Iquesters\Foundation\Support\ConfProvider;
use Illuminate\Console\Command;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Dev\Config\DevConf;
use Iquesters\Dev\Database\Seeders\DevSeeder;

class DevServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        ConfProvider::register(Module::DEV, DevConf::class);

        // Register the seed command
        $this->registerSeedCommand();
    }

    public function boot(): void
    {
        // $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        // $this->loadViewsFrom(__DIR__ . '/../resources/views', 'dev');
        
        $this->app->instance('app.layout', $this->getAppLayout());
    
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/dev.php' => config_path('dev.php'),
        ], 'dev-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                'command.dev.seed'
            ]);
        }
    }
    
    protected function getAppLayout(): string
    {
        // Try UserInterface first
        if (class_exists(UserInterfaceConf::class)) {
            try {
                $uiConf = ConfProvider::from(Module::USER_INFE);

                if (method_exists($uiConf, 'ensureLoaded')) {
                    $uiConf->ensureLoaded();
                }

                return $uiConf->app_layout;
            } catch (\Throwable $e) {
                Log::warning('Dev: failed to load UserInterface app layout', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback → Dev layout
        return 'dev::layouts.app';
    }

    /**
     * Register the dev seed command.
     */
    protected function registerSeedCommand(): void
    {
        $this->app->singleton('command.dev.seed', function ($app) {
            return new class extends Command {
                protected $signature = 'dev:seed';
                protected $description = 'Seed dev data from the package';

                public function handle()
                {
                    $this->info('Running Dev Seeder...');

                    $seeder = new DevSeeder();
                    $seeder->setCommand($this);
                    $seeder->run();

                    return 0;
                }
            };
        });
    }
}