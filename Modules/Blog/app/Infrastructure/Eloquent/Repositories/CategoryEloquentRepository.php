<?php

namespace Modules\Blog\Infrastructure\Eloquent\Repositories;

use Modules\Blog\Domain\Repositories\CategoryRepositoryInterface;
use Modules\Blog\Infrastructure\Eloquent\Models\Category;
use Modules\Shared\Infrastructure\Eloquent\FilterableRepository;

class CategoryEloquentRepository extends FilterableRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }
}
