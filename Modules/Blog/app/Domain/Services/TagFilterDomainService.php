<?php

namespace Modules\Blog\Domain\Services;

use Modules\Blog\Domain\DTOs\FilterResult;
use Modules\Blog\Domain\Repositories\TagRepositoryInterface;

class TagFilterDomainService
{
    public function __construct(
        protected TagRepositoryInterface $repository,
    ) {}

    public function applyFilters(array $validatedFilters): FilterResult
    {
        return $this->repository->filter($validatedFilters);
    }
}
