<?php

namespace Modules\Blog\Domain\DTOs;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FilterResult
{
    public function __construct(
        public readonly LengthAwarePaginator $paginator,
    ) {}

    public function toArray(): array
    {
        return $this->paginator->toArray();
    }
}
