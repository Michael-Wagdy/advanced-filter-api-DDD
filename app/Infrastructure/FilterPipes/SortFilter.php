<?php

namespace App\Infrastructure\FilterPipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class SortFilter
{
    public function __construct(
        protected ?string $sortParam,
    ) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if (!$this->sortParam || trim($this->sortParam) === '') {
            return $next($query);
        }

        $direction = 'ASC';
        $column = $this->sortParam;

        if (str_starts_with($this->sortParam, '-')) {
            $direction = 'DESC';
            $column = substr($this->sortParam, 1);
        } elseif (str_starts_with($this->sortParam, '+')) {
            $direction = 'ASC';
            $column = substr($this->sortParam, 1);
        }

        $table = $query->getModel()->getTable();
        $qualifiedColumn = "{$table}.{$column}";

        $query->orderBy($qualifiedColumn, $direction);

        return $next($query);
    }
}
