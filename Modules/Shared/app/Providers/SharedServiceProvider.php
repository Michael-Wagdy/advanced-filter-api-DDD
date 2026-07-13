<?php

namespace Modules\Shared\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class SharedServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Shared';
    protected string $nameLower = 'shared';

    public function register(): void {}

    public function boot(): void {}
}
