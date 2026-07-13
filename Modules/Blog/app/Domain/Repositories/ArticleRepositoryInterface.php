<?php

namespace Modules\Blog\Domain\Repositories;

use Modules\Blog\Domain\DTOs\FilterResult;

interface ArticleRepositoryInterface
{
    public function filter(array $filters): FilterResult;
}
