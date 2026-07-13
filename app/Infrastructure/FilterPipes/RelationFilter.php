<?php

namespace App\Infrastructure\FilterPipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;

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

    private const RELATION_MAP = [
        'article' => [
            'model' => \Modules\Blog\Infrastructure\Eloquent\Models\Article::class,
            'relations' => [
                'category' => 'category_id',
                'user' => 'user_id',
                'tags' => 'article_tag',
                'comments' => 'comments',
            ],
        ],
        'category' => [
            'model' => \Modules\Blog\Infrastructure\Eloquent\Models\Category::class,
            'relations' => [
                'articles' => 'articles',
            ],
        ],
        'tag' => [
            'model' => \Modules\Blog\Infrastructure\Eloquent\Models\Tag::class,
            'relations' => [
                'articles' => 'article_tag',
            ],
        ],
    ];

    public function handle(Builder $query, Closure $next): Builder
    {
        $fieldFilters = request()->input('filter', []);
        $modelName = class_basename($query->getModel());

        $relationMap = match ($modelName) {
            'Article' => 'article',
            'Category' => 'category',
            'Tag' => 'tag',
            default => null,
        };

        if (!$relationMap || !isset(self::RELATION_MAP[$relationMap])) {
            return $next($query);
        }

        $configuredRelations = self::RELATION_MAP[$relationMap]['relations'];

        foreach ($fieldFilters as $relation => $conditions) {
            if (!isset($configuredRelations[$relation]) || !is_array($conditions)) {
                continue;
            }

            $this->applyRelationFilter($query, $relation, $conditions, $configuredRelations[$relation]);
        }

        return $next($query);
    }

    private function applyRelationFilter(Builder $query, string $relation, array $conditions, string $foreignKey): void
    {
        foreach ($conditions as $field => $operatorConditions) {
            if (!is_array($operatorConditions)) {
                continue;
            }

            if ($relation === 'comments') {
                $firstKey = array_key_first($operatorConditions);
                if ($firstKey !== null && !array_key_exists($firstKey, self::OPERATORS)) {
                    $this->filterByCommentsNested($query, $field, $operatorConditions);
                    continue;
                }
            }

            foreach ($operatorConditions as $operator => $value) {
                if (!array_key_exists($operator, self::OPERATORS)) {
                    continue;
                }

                $sqlOperator = self::OPERATORS[$operator];

                match ($relation) {
                    'category' => $this->filterByCategory($query, $field, $operator, $value, $sqlOperator),
                    'tags' => $this->filterByTags($query, $field, $operator, $value, $sqlOperator),
                    'user' => $this->filterByUser($query, $field, $operator, $value, $sqlOperator),
                    'comments' => $this->filterByComments($query, $field, $operator, $value, $sqlOperator),
                    default => null,
                };
            }
        }
    }

    private function filterByCategory(Builder $query, string $field, string $operator, mixed $value, string $sqlOperator): void
    {
        $query->whereHas('category', function (Builder $q) use ($field, $sqlOperator, $operator, $value) {
            if ($operator === 'empty') {
                $q->whereNull('id');
            } elseif ($operator === 'filled') {
                $q->whereNotNull('id');
            } elseif ($operator === 'in') {
                $q->whereIn($field, is_array($value) ? $value : explode(',', $value));
            } elseif ($operator === 'like') {
                $q->where($field, 'LIKE', "%{$value}%");
            } else {
                $q->where($field, $sqlOperator, $value);
            }
        });
    }

    private function filterByTags(Builder $query, string $field, string $operator, mixed $value, string $sqlOperator): void
    {
        $query->whereHas('tags', function (Builder $q) use ($field, $sqlOperator, $operator, $value) {
            if ($operator === 'empty') {
                $q->whereNull('id');
            } elseif ($operator === 'filled') {
                $q->whereNotNull('id');
            } elseif ($operator === 'in') {
                $q->whereIn($field, is_array($value) ? $value : explode(',', $value));
            } elseif ($operator === 'like') {
                $q->where($field, 'LIKE', "%{$value}%");
            } else {
                $q->where($field, $sqlOperator, $value);
            }
        });
    }

    private function filterByUser(Builder $query, string $field, string $operator, mixed $value, string $sqlOperator): void
    {
        $query->whereHas('user', function (Builder $q) use ($field, $sqlOperator, $operator, $value) {
            if ($operator === 'empty') {
                $q->whereNull('id');
            } elseif ($operator === 'filled') {
                $q->whereNotNull('id');
            } elseif ($operator === 'in') {
                $q->whereIn($field, is_array($value) ? $value : explode(',', $value));
            } elseif ($operator === 'like') {
                $q->where($field, 'LIKE', "%{$value}%");
            } else {
                $q->where($field, $sqlOperator, $value);
            }
        });
    }

    private function filterByCommentsNested(Builder $query, string $field, array $conditions): void
    {
        $query->whereHas("comments.{$field}", function (Builder $q) use ($field, $conditions) {
            foreach ($conditions as $nestedField => $nestedOperators) {
                if (!is_array($nestedOperators)) continue;
                foreach ($nestedOperators as $operator => $value) {
                    if (!array_key_exists($operator, self::OPERATORS)) continue;
                    $sqlOperator = self::OPERATORS[$operator];
                    match ($operator) {
                        'like' => $q->where("users.{$nestedField}", 'LIKE', "%{$value}%"),
                        'empty' => $q->whereNull("users.{$nestedField}"),
                        'filled' => $q->whereNotNull("users.{$nestedField}"),
                        'in' => $q->whereIn("users.{$nestedField}", is_array($value) ? $value : explode(',', $value)),
                        default => $q->where("users.{$nestedField}", $sqlOperator, $value),
                    };
                }
            }
        });
    }

    private function filterByComments(Builder $query, string $field, string $operator, mixed $value, string $sqlOperator): void
    {
        $query->whereHas('comments.user', function (Builder $q) use ($field, $sqlOperator, $operator, $value) {
            if ($operator === 'empty') {
                $q->whereNull('id');
            } elseif ($operator === 'filled') {
                $q->whereNotNull('id');
            } elseif ($operator === 'in') {
                $q->whereIn("users.{$field}", is_array($value) ? $value : explode(',', $value));
            } elseif ($operator === 'like') {
                $q->where("users.{$field}", 'LIKE', "%{$value}%");
            } else {
                $q->where("users.{$field}", $sqlOperator, $value);
            }
        });
    }
}
