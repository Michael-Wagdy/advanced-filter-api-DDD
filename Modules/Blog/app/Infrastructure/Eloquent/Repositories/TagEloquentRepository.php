<?php

namespace Modules\Blog\Infrastructure\Eloquent\Repositories;

use Modules\Blog\Domain\DTOs\FilterResult;
use Modules\Blog\Domain\Repositories\TagRepositoryInterface;
use Modules\Blog\Infrastructure\Eloquent\Models\Tag;

class TagEloquentRepository implements TagRepositoryInterface
{
    public function __construct(
        protected Tag $model,
    ) {}

    public function filter(array $filters): FilterResult
    {
        $builder = $this->model->newQuery()->withDefaultEagerLoads();

        $result = $builder
            ->whereFieldFilters($filters['filter'] ?? [], $builder->getRelationFields())
            ->whereSearch($filters['search'] ?? '', $builder->getSearchableColumns())
            ->applySort($filters['sort'] ?? null);

        $perPage = $filters['per_page'] ?? 15;

        return new FilterResult($result->paginate((int) $perPage));
    }
}
