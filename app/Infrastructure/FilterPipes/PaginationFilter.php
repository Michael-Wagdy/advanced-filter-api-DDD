<?php

namespace App\Infrastructure\FilterPipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class PaginationFilter
{
    private const DEFAULT_PER_PAGE = 15;
    private const MAX_PER_PAGE = 100;

    public function handle(Builder $query, Closure $next): Builder
    {
        return $next($query);
    }

    public static function resolvePerPage(): int
    {
        $perPage = (int) request()->input('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}
