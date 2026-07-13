<?php

namespace App\Infrastructure\FilterPipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RelationFilter
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
        protected array $relationFilters,
    ) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        $model = $query->getModel();

        foreach ($this->relationFilters as $key => $conditions) {
            if (!is_array($conditions)) {
                continue;
            }

            if (str_contains($key, '.')) {
                $this->applyDotNotationFilter($query, $key, $conditions, $model);
            } elseif (method_exists($model, $key)) {
                $query->whereHas($key, function (Builder $q) use ($conditions, $key, $model) {
                    $related = $this->resolveRelatedModel($model, $key);
                    $this->processBranch($q, $conditions, $related);
                });
            }
        }

        return $next($query);
    }

    private function applyDotNotationFilter(Builder $query, string $dotPath, array $conditions, Model $model): void
    {
        $segments = explode('.', $dotPath);
        $field = array_pop($segments);

        $query->whereHas(implode('.', $segments), function (Builder $q) use ($field, $conditions) {
            foreach ($conditions as $operator => $value) {
                if (!array_key_exists($operator, self::OPERATORS)) {
                    continue;
                }

                $this->applyOperator($q, $field, $operator, $value);
            }
        });
    }

    private function processBranch(Builder $query, array $conditions, Model $model): void
    {
        foreach ($conditions as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if (method_exists($model, $key)) {
                $nested = $this->resolveRelatedModel($model, $key);
                $query->whereHas($key, function (Builder $q) use ($value, $nested) {
                    $this->processBranch($q, $value, $nested);
                });
            } else {
                foreach ($value as $operator => $operand) {
                    if (!array_key_exists($operator, self::OPERATORS)) {
                        continue;
                    }

                    $this->applyOperator($query, $key, $operator, $operand);
                }
            }
        }
    }

    private function applyOperator(Builder $query, string $field, string $operator, mixed $value): void
    {
        match ($operator) {
            'like' => $query->where($field, 'LIKE', "%{$value}%"),
            'empty' => $query->whereNull($field),
            'filled' => $query->whereNotNull($field),
            'in' => $query->whereIn($field, is_array($value) ? $value : explode(',', $value)),
            default => $query->where($field, self::OPERATORS[$operator], $value),
        };
    }

    private function resolveRelatedModel(Model $parent, string $relation): Model
    {
        return $parent->{$relation}()->getRelated();
    }
}
