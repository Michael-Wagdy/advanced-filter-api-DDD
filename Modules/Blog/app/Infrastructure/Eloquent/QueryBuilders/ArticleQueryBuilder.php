<?php

namespace Modules\Blog\Infrastructure\Eloquent\QueryBuilders;

use Modules\Shared\Infrastructure\QueryBuilders\FilterableBuilder;

class ArticleQueryBuilder extends FilterableBuilder
{
    public function withDefaultEagerLoads(): static
    {
        return $this->with(['category', 'tags']);
    }

    public function getRelationFields(): array
    {
        return ['category', 'tags', 'comments', 'user'];
    }

    public function getSearchableColumns(): array
    {
        return ['title', 'body'];
    }
}
