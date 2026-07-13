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
    private const SEARCHABLE_COLUMNS = ['name', 'description'];
    private const RELATION_FIELDS = ['articles'];

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
                new FieldFilter($filters['filter'] ?? [], self::RELATION_FIELDS),
                new SearchFilter($filters['search'] ?? '', self::SEARCHABLE_COLUMNS),
                new SortFilter($filters['sort'] ?? null),
            ])
            ->thenReturn();

        $perPage = new PaginationFilter($filters['per_page'] ?? PaginationFilter::DEFAULT_PER_PAGE);

        return $result->paginate($perPage->resolvePerPage());
    }
}
