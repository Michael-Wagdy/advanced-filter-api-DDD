<?php

namespace Modules\Blog\Infrastructure\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pipeline\Pipeline;
use Modules\Blog\Domain\Repositories\ArticleRepositoryInterface;
use App\Infrastructure\FilterPipes\FieldFilter;
use App\Infrastructure\FilterPipes\PaginationFilter;
use App\Infrastructure\FilterPipes\RelationFilter;
use App\Infrastructure\FilterPipes\SearchFilter;
use App\Infrastructure\FilterPipes\SortFilter;
use Modules\Blog\Infrastructure\Eloquent\Models\Article;

class ArticleEloquentRepository implements ArticleRepositoryInterface
{
    public function __construct(
        protected Article $model,
    ) {}

    public function filter(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['category', 'tags']);

        $result = app(Pipeline::class)
            ->send($query)
            ->through([
                FieldFilter::class,
                RelationFilter::class,
                SearchFilter::class,
                SortFilter::class,
            ])
            ->thenReturn();

        $perPage = PaginationFilter::resolvePerPage();

        return $result->paginate($perPage);
    }
}
