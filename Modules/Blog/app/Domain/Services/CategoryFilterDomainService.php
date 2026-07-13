<?php

namespace Modules\Blog\Domain\Services;

use Modules\Blog\Domain\DTOs\FilterResult;
use Modules\Blog\Domain\Repositories\CategoryRepositoryInterface;

class CategoryFilterDomainService
{
    public function __construct(
        protected CategoryRepositoryInterface $repository,
    ) {}

    public function applyFilters(array $validatedFilters): FilterResult
    {
        return $this->repository->filter($validatedFilters);
    }
}
