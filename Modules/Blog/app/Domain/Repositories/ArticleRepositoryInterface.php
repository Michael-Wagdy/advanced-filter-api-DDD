<?php

namespace Modules\Blog\Domain\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ArticleRepositoryInterface
{
    public function filter(array $filters): LengthAwarePaginator;
}
