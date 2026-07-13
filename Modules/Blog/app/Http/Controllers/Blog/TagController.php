<?php

namespace Modules\Blog\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Modules\Blog\Domain\Services\TagFilterDomainService;
use Modules\Blog\Http\Resources\TagResource;

class TagController extends Controller
{
    public function __construct(
        protected TagFilterDomainService $service,
    ) {}

    public function index()
    {
        $filters = request()->only(['filter', 'search', 'sort', 'per_page']);

        $tags = $this->service->applyFilters($filters);

        return TagResource::collection($tags);
    }
}
