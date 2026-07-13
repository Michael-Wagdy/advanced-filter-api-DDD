<?php

namespace App\Infrastructure\FilterPipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class FieldFilter
{
    private const OPERATORS = [
        'like' => 'LIKE',
        'eq' => '=',
        'neq' => '!=',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'in' => 'IN',
        'empty' => 'IS_NULL',
        'filled' => 'IS_NOT_NULL',
    ];

    public function __construct(
        protected array $fieldFilters,
        protected array $relationFields,
    ) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        $table = $query->getModel()->getTable();

        foreach ($this->fieldFilters as $field => $conditions) {
            if (str_contains($field, '.') || in_array($field, $this->relationFields)) {
                continue;
            }

            if (!is_array($conditions)) {
                continue;
            }

            foreach ($conditions as $operator => $value) {
                if (!array_key_exists($operator, self::OPERATORS)) {
                    continue;
                }

                $column = "{$table}.{$field}";
                $sqlOperator = self::OPERATORS[$operator];

                match ($operator) {
                    'like' => $query->where($column, 'LIKE', "%{$value}%"),
                    'empty' => $query->whereNull($column),
                    'filled' => $query->whereNotNull($column),
                    'in' => $query->whereIn($column, is_array($value) ? $value : explode(',', $value)),
                    default => $query->where($column, $sqlOperator, $value),
                };
            }
        }

        return $next($query);
    }
}
