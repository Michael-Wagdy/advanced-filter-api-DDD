<?php

namespace Modules\Blog\Infrastructure\Eloquent\Repositories;

use Modules\Blog\Domain\Repositories\TagRepositoryInterface;
use Modules\Blog\Infrastructure\Eloquent\Models\Tag;
use Modules\Shared\Infrastructure\Eloquent\FilterableRepository;

class TagEloquentRepository extends FilterableRepository implements TagRepositoryInterface
{
    public function __construct(Tag $model)
    {
        parent::__construct($model);
    }
}
