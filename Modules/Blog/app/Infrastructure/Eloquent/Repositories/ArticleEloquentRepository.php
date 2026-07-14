<?php

namespace Modules\Blog\Infrastructure\Eloquent\Repositories;

use Modules\Blog\Domain\Repositories\ArticleRepositoryInterface;
use Modules\Blog\Infrastructure\Eloquent\Models\Article;
use Modules\Shared\Infrastructure\Eloquent\FilterableRepository;

class ArticleEloquentRepository extends FilterableRepository implements ArticleRepositoryInterface
{
    public function __construct(Article $model)
    {
        parent::__construct($model);
    }
}
