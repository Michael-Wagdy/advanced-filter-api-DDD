<?php

namespace Modules\Blog\Domain\Services;

use Modules\Blog\Domain\DTOs\FilterResult;
use Modules\Blog\Domain\Repositories\ArticleRepositoryInterface;

class ArticleFilterDomainService
{
    public function __construct(
        protected ArticleRepositoryInterface $repository,
    ) {}

    public function applyFilters(array $validatedFilters): FilterResult
    {
        return $this->repository->filter($validatedFilters);
    }
}
