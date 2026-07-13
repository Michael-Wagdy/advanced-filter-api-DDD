<?php

namespace Modules\Blog\Infrastructure\Eloquent\QueryBuilders;

use Modules\Shared\Infrastructure\QueryBuilders\FilterableBuilder;

class TagQueryBuilder extends FilterableBuilder
{
    public function withDefaultEagerLoads(): static
    {
        return $this->withCount('articles');
    }

    public function getRelationFields(): array
    {
        return ['articles'];
    }

    public function getSearchableColumns(): array
    {
        return ['name'];
    }
}
