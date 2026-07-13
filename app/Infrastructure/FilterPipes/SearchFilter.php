<?php

namespace App\Infrastructure\FilterPipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SearchFilter
{
    public function handle(Builder $query, Closure $next): Builder
    {
        $search = request()->input('search');

        if (!$search || trim($search) === '') {
            return $next($query);
        }

        $searchTerm = trim($search);
        $driver = DB::connection()->getDriverName();

        $modelName = class_basename($query->getModel());

        if ($modelName === 'Article' && in_array($driver, ['mysql', 'mariadb'])) {
            $query->whereFullText(['title', 'body'], $searchTerm);
        } elseif ($modelName === 'Category' && in_array($driver, ['mysql', 'mariadb'])) {
            $query->whereFullText(['name', 'description'], $searchTerm);
        } elseif ($modelName === 'Article') {
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('body', 'LIKE', "%{$searchTerm}%");
            });
        } elseif ($modelName === 'Category') {
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        } elseif ($modelName === 'Tag') {
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        return $next($query);
    }
}
