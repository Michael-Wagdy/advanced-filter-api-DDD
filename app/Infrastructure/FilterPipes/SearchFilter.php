<?php

namespace App\Infrastructure\FilterPipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SearchFilter
{
    public function __construct(
        protected string $searchTerm,
        protected array $searchableColumns,
    ) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if ($this->searchTerm === '' || empty($this->searchableColumns)) {
            return $next($query);
        }

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            $query->whereFullText($this->searchableColumns, $this->searchTerm);
        } else {
            $query->where(function (Builder $q) {
                foreach ($this->searchableColumns as $column) {
                    $q->orWhere($column, 'LIKE', "%{$this->searchTerm}%");
                }
            });
        }

        return $next($query);
    }
}
