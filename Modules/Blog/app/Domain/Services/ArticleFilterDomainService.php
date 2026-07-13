<?php

namespace Modules\Blog\Domain\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Blog\Domain\Repositories\ArticleRepositoryInterface;

class ArticleFilterDomainService
{
    public function __construct(
        protected ArticleRepositoryInterface $repository,
    ) {}

    public function applyFilters(array $validatedFilters): LengthAwarePaginator
    {
        return $this->repository->filter($validatedFilters);
    }
}
