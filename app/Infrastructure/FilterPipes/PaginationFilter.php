<?php

namespace App\Infrastructure\FilterPipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class PaginationFilter
{
    public const DEFAULT_PER_PAGE = 15;
    private const MAX_PER_PAGE = 100;

    public function __construct(
        protected int $perPage,
    ) {
        $this->perPage = max(1, min($perPage, self::MAX_PER_PAGE));
    }

    public function handle(Builder $query, Closure $next): Builder
    {
        return $next($query);
    }

    public function resolvePerPage(): int
    {
        return $this->perPage;
    }
}
