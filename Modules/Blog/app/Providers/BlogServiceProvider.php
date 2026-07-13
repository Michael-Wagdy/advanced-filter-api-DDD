<?php

namespace Modules\Blog\Providers;

use Illuminate\Support\Facades\Route;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Modules\Blog\Domain\Repositories\ArticleRepositoryInterface;
use Modules\Blog\Domain\Repositories\CategoryRepositoryInterface;
use Modules\Blog\Domain\Repositories\TagRepositoryInterface;
use Modules\Blog\Infrastructure\Eloquent\Repositories\ArticleEloquentRepository;
use Modules\Blog\Infrastructure\Eloquent\Repositories\CategoryEloquentRepository;
use Modules\Blog\Infrastructure\Eloquent\Repositories\TagEloquentRepository;
use Modules\Blog\Http\Middleware\PerformanceTelemetry;

class BlogServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Blog';
    protected string $nameLower = 'blog';

    protected array $providers = [
        EventServiceProvider::class,
    ];

    public function register(): void
    {
        $this->app->bind(ArticleRepositoryInterface::class, ArticleEloquentRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryEloquentRepository::class);
        $this->app->bind(TagRepositoryInterface::class, TagEloquentRepository::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['api', PerformanceTelemetry::class])
            ->prefix('api/v1/blog')
            ->group(base_path('Modules/Blog/routes/api.php'));
    }
}
