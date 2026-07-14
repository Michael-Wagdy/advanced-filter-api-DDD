<?php

namespace Modules\Shared\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Modules\Blog\Domain\DTOs\FilterResult;

abstract class FilterableRepository
{
    public function __construct(
        protected Model $model,
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
