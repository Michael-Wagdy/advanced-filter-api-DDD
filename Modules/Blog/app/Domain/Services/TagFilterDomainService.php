<?php

namespace Modules\Blog\Domain\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Blog\Domain\Repositories\TagRepositoryInterface;

class TagFilterDomainService
{
    public function __construct(
        protected TagRepositoryInterface $repository,
    ) {}

    public function applyFilters(array $validatedFilters): LengthAwarePaginator
    {
        return $this->repository->filter($validatedFilters);
    }
}
