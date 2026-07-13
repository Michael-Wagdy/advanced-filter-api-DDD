<?php

namespace Modules\Blog\Domain\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TagRepositoryInterface
{
    public function filter(array $filters): LengthAwarePaginator;
}
