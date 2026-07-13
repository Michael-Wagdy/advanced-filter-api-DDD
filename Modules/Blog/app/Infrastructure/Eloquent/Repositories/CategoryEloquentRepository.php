<?php

namespace Modules\Blog\Infrastructure\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pipeline\Pipeline;
use Modules\Blog\Domain\Repositories\CategoryRepositoryInterface;
use App\Infrastructure\FilterPipes\FieldFilter;
use App\Infrastructure\FilterPipes\PaginationFilter;
use App\Infrastructure\FilterPipes\SearchFilter;
use App\Infrastructure\FilterPipes\SortFilter;
use Modules\Blog\Infrastructure\Eloquent\Models\Category;

class CategoryEloquentRepository implements CategoryRepositoryInterface
{
    public function __construct(
        protected Category $model,
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
