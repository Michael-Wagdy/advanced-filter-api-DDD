<?php

namespace Modules\Blog\Domain\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Blog\Domain\Repositories\CategoryRepositoryInterface;

class CategoryFilterDomainService
{
    public function __construct(
        protected CategoryRepositoryInterface $repository,
    ) {}

    public function applyFilters(array $validatedFilters): LengthAwarePaginator
    {
        return $this->repository->filter($validatedFilters);
    }
}
