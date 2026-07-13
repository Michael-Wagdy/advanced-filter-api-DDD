<?php

namespace Modules\Blog\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Modules\Blog\Domain\Services\TagFilterDomainService;
use Modules\Blog\Http\Requests\FilterTagsRequest;
use Modules\Blog\Http\Resources\TagResource;

class TagController extends Controller
{
    public function __construct(
        protected TagFilterDomainService $service,
    ) {}

    public function index(FilterTagsRequest $request)
    {
        $filters = $request->only(['filter', 'search', 'sort', 'per_page']);

        $result = $this->service->applyFilters($filters);

        return TagResource::collection($result->paginator);
    }
}
