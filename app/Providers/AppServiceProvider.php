<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureFactories();
    }

    protected function configureFactories(): void
    {
        Factory::guessFactoryNamesUsing(function (string $modelName) {
            if (str_starts_with($modelName, 'Modules\\')) {
                $parts = explode('\\', $modelName);
                $moduleName = $parts[1];
                $baseName = class_basename($modelName);

                return "Modules\\{$moduleName}\\Database\\Factories\\{$baseName}Factory";
            }

            return 'Database\\Factories\\' . class_basename($modelName) . 'Factory';
        });
    }
}
