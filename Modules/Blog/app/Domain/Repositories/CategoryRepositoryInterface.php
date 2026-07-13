<?php

namespace Modules\Blog\Domain\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoryRepositoryInterface
{
    public function filter(array $filters): LengthAwarePaginator;
}
