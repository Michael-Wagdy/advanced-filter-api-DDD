<?php

namespace Modules\Blog\Infrastructure\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pipeline\Pipeline;
use Modules\Blog\Domain\Repositories\TagRepositoryInterface;
use App\Infrastructure\FilterPipes\FieldFilter;
use App\Infrastructure\FilterPipes\PaginationFilter;
use App\Infrastructure\FilterPipes\SearchFilter;
use App\Infrastructure\FilterPipes\SortFilter;
use Modules\Blog\Infrastructure\Eloquent\Models\Tag;

class TagEloquentRepository implements TagRepositoryInterface
{
    public function __construct(
        protected Tag $model,
    ) {}

    public function filter(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->withCount('articles');

        $result = app(Pipeline::class)
            ->send($query)
            ->through([
                FieldFilter::class,
                SearchFilter::class,
                SortFilter::class,
            ])
            ->thenReturn();

        $perPage = PaginationFilter::resolvePerPage();

        return $result->paginate($perPage);
    }
}
