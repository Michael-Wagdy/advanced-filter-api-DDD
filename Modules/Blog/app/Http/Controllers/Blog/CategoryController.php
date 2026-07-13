<?php

namespace Modules\Blog\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Modules\Blog\Domain\Services\CategoryFilterDomainService;
use Modules\Blog\Http\Requests\FilterCategoriesRequest;
use Modules\Blog\Http\Resources\CategoryResource;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryFilterDomainService $service,
    ) {}

    public function index(FilterCategoriesRequest $request)
    {
        $filters = $request->only(['filter', 'search', 'sort', 'per_page']);

        $result = $this->service->applyFilters($filters);

        return CategoryResource::collection($result->paginator);
    }
}
