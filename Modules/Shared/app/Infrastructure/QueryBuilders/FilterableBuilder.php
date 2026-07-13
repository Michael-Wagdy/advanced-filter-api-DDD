<?php

namespace Modules\Shared\Infrastructure\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

class FilterableBuilder extends Builder
{
    private array $joinedTables = [];

    public const ALLOWED_OPERATORS = [
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

    public function whereFieldFilters(array $filters, array $relationFields = []): static
    {
        $table = $this->getModel()->getTable();

        foreach ($filters as $field => $conditions) {
            if (str_contains($field, '.') || in_array($field, $relationFields)) {
                continue;
            }

            if (!is_array($conditions)) {
                continue;
            }

            foreach ($conditions as $operator => $value) {
                if (!array_key_exists($operator, self::ALLOWED_OPERATORS)) {
                    continue;
                }

                $column = "{$table}.{$field}";
                $this->applyOperator($column, $operator, $value);
            }
        }

        return $this;
    }

    public function whereRelationFilters(array $filters): static
    {
        $model = $this->getModel();

        foreach ($filters as $key => $conditions) {
            if (!is_array($conditions)) {
                continue;
            }

            if (str_contains($key, '.')) {
                $this->applyDotNotationFilter($key, $conditions, $model);
            } elseif (method_exists($model, $key)) {
                $this->applyNestedFilter($key, $conditions, $model);
            }
        }

        return $this;
    }

    public function whereSearch(string $term, array $columns): static
    {
        if ($term === '' || empty($columns)) {
            return $this;
        }

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            $this->whereFullText($columns, $term);
        } else {
            $this->where(function (Builder $q) use ($term, $columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', "%{$term}%");
                }
            });
        }

        return $this;
    }

    public function applySort(?string $sortParam): static
    {
        if (!$sortParam || trim($sortParam) === '') {
            return $this;
        }

        $direction = 'ASC';
        $column = $sortParam;

        if (str_starts_with($sortParam, '-')) {
            $direction = 'DESC';
            $column = substr($sortParam, 1);
        } elseif (str_starts_with($sortParam, '+')) {
            $column = substr($sortParam, 1);
        }

        $table = $this->getModel()->getTable();
        $this->orderBy("{$table}.{$column}", $direction);

        return $this;
    }

    private function applyDotNotationFilter(string $dotPath, array $conditions, Model $model): void
    {
        $segments = explode('.', $dotPath);
        $field = array_pop($segments);

        $currentModel = $model;
        $lastAlias = null;

        foreach ($segments as $segment) {
            if (!method_exists($currentModel, $segment)) {
                break;
            }

            $relation = $currentModel->{$segment}();
            $lastAlias = $this->joinRelation($relation, $currentModel);
            $currentModel = $relation->getRelated();
        }

        $qualifiedField = $lastAlias ? "{$lastAlias}.{$field}" : $field;

        foreach ($conditions as $operator => $value) {
            if (!array_key_exists($operator, self::ALLOWED_OPERATORS)) {
                continue;
            }

            $this->applyOperator($qualifiedField, $operator, $value);
        }
    }

    private function applyNestedFilter(string $relationName, array $conditions, Model $model): void
    {
        $relation = $model->{$relationName}();
        $alias = $this->joinRelation($relation, $model);
        $related = $relation->getRelated();

        $this->processBranch($conditions, $related, $alias);
    }

    private function processBranch(array $conditions, Model $model, string $parentAlias): void
    {
        foreach ($conditions as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if (method_exists($model, $key)) {
                $relation = $model->{$key}();
                $alias = $this->joinRelation($relation, $model);
                $nested = $relation->getRelated();
                $this->processBranch($value, $nested, $alias);
            } else {
                $qualifiedField = "{$parentAlias}.{$key}";
                foreach ($value as $operator => $operand) {
                    if (!array_key_exists($operator, self::ALLOWED_OPERATORS)) {
                        continue;
                    }
                    $this->applyOperator($qualifiedField, $operator, $operand);
                }
            }
        }
    }

    private function joinRelation(Relation $relation, Model $parent): string
    {
        $relatedTable = $relation->getRelated()->getTable();

        if (in_array($relatedTable, $this->joinedTables)) {
            return $relatedTable;
        }

        $parentTable = $parent->getTable();

        if ($relation instanceof BelongsTo) {
            $foreignKey = $relation->getForeignKeyName();
            $ownerKey = $relation->getOwnerKeyName();

            $this->join(
                $relatedTable,
                "{$relatedTable}.{$ownerKey}",
                '=',
                "{$parentTable}.{$foreignKey}"
            );
        } elseif ($relation instanceof HasMany || $relation instanceof HasOne) {
            $foreignKey = $relation->getForeignKeyName();
            $localKey = $relation->getLocalKeyName();

            $this->join(
                $relatedTable,
                "{$relatedTable}.{$foreignKey}",
                '=',
                "{$parentTable}.{$localKey}"
            );
        } elseif ($relation instanceof BelongsToMany) {
            $pivotTable = $relation->getTable();
            $foreignPivotKey = $relation->getForeignPivotKeyName();
            $parentKey = $relation->getParentKeyName();
            $relatedPivotKey = $relation->getRelatedPivotKeyName();

            $this->join(
                $pivotTable,
                "{$pivotTable}.{$foreignPivotKey}",
                '=',
                "{$parentTable}.{$parentKey}"
            );

            $relatedKeyName = $relation->getRelatedKeyName();

            $this->join(
                $relatedTable,
                "{$relatedTable}.{$relatedKeyName}",
                '=',
                "{$pivotTable}.{$relatedPivotKey}"
            );
        }

        $this->joinedTables[] = $relatedTable;

        return $relatedTable;
    }

    private function applyOperator(string $column, string $operator, mixed $value): void
    {
        match ($operator) {
            'like' => $this->where($column, 'LIKE', "{$value}%"),
            'empty' => $this->whereNull($column),
            'filled' => $this->whereNotNull($column),
            'in' => $this->whereIn($column, is_array($value) ? $value : explode(',', $value)),
            default => $this->where($column, self::ALLOWED_OPERATORS[$operator], $value),
        };
    }
}
