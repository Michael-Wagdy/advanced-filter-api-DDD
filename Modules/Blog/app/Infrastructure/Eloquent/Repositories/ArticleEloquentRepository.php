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
    private const SEARCHABLE_COLUMNS = ['title', 'body'];
    private const RELATION_FIELDS = ['category', 'tags', 'comments', 'user'];

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
                new FieldFilter($filters['filter'] ?? [], self::RELATION_FIELDS),
                new RelationFilter($filters['filter'] ?? []),
                new SearchFilter($filters['search'] ?? '', self::SEARCHABLE_COLUMNS),
                new SortFilter($filters['sort'] ?? null),
            ])
            ->thenReturn();

        $perPage = new PaginationFilter($filters['per_page'] ?? PaginationFilter::DEFAULT_PER_PAGE);

        return $result->paginate($perPage->resolvePerPage());
    }
}
