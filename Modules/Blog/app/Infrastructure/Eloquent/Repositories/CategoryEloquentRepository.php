<?php

namespace Modules\Blog\Infrastructure\Eloquent\Repositories;

use Modules\Blog\Domain\DTOs\FilterResult;
use Modules\Blog\Domain\Repositories\CategoryRepositoryInterface;
use Modules\Blog\Infrastructure\Eloquent\Models\Category;

class CategoryEloquentRepository implements CategoryRepositoryInterface
{
    public function __construct(
        protected Category $model,
    ) {}

    public function filter(array $filters): FilterResult
    {
        $builder = $this->model->newQuery()->withDefaultEagerLoads();

        $result = $builder
            ->whereFieldFilters($filters['filter'] ?? [], $builder->getRelationFields())
            ->whereRelationFilters($filters['filter'] ?? [])
            ->whereSearch($filters['search'] ?? '', $builder->getSearchableColumns())
            ->applySort($filters['sort'] ?? null);

        $perPage = $filters['per_page'] ?? 15;

        return new FilterResult($result->paginate((int) $perPage));
    }
}
