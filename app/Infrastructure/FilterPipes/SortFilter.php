<?php

namespace App\Infrastructure\FilterPipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class SortFilter
{
    private const DIRECTION_MAP = [
        '-' => 'DESC',
        '+' => 'ASC',
    ];

    public function handle(Builder $query, Closure $next): Builder
    {
        $sort = request()->input('sort');

        if (!$sort || trim($sort) === '') {
            return $next($query);
        }

        $direction = 'ASC';
        $column = $sort;

        if (str_starts_with($sort, '-')) {
            $direction = 'DESC';
            $column = substr($sort, 1);
        } elseif (str_starts_with($sort, '+')) {
            $direction = 'ASC';
            $column = substr($sort, 1);
        }

        $table = $query->getModel()->getTable();
        $qualifiedColumn = "{$table}.{$column}";

        $query->orderBy($qualifiedColumn, $direction);

        return $next($query);
    }
}
