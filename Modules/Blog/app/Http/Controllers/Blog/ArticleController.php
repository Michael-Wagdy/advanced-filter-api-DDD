<?php

namespace Modules\Blog\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Modules\Blog\Domain\Services\ArticleFilterDomainService;
use Modules\Blog\Http\Requests\FilterArticlesRequest;
use Modules\Blog\Http\Resources\ArticleResource;

class ArticleController extends Controller
{
    public function __construct(
        protected ArticleFilterDomainService $service,
    ) {}

    public function index(FilterArticlesRequest $request)
    {
        $filters = $request->only(['filter', 'search', 'sort', 'per_page']);

        $result = $this->service->applyFilters($filters);

        return ArticleResource::collection($result->paginator);
    }
}
