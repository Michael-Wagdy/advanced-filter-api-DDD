<?php

namespace Modules\Blog\Tests\Feature\Filtering;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Infrastructure\Eloquent\Models\Article;
use Modules\Blog\Infrastructure\Eloquent\Models\Category;
use Tests\TestCase;

class CategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'api/v1/blog/categories';

    protected User $user;
    protected array $categories = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->categories[] = Category::factory()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
            'is_active' => true,
        ]);

        $this->categories[] = Category::factory()->create([
            'name' => 'JavaScript',
            'slug' => 'javascript',
            'is_active' => true,
        ]);

        $this->categories[] = Category::factory()->create([
            'name' => 'Legacy PHP',
            'slug' => 'legacy-php',
            'is_active' => false,
        ]);

        Article::factory()->count(5)->create([
            'category_id' => $this->categories[0]->id,
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);

        Article::factory()->count(2)->create([
            'category_id' => $this->categories[1]->id,
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);
    }

    public function test_categories_list_returns_paginated_results(): void
    {
        $response = $this->getJson(self::ENDPOINT);

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'articles_count'],
                ],
            ]);
    }

    public function test_filter_categories_by_exact_name(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[name][eq]=Laravel');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laravel');
    }

    public function test_filter_categories_by_like_name(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[name][like]=Legac');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_categories_by_is_active(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[is_active][eq]=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_sort_categories_by_name(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?sort=-name');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Legacy PHP')
            ->assertJsonPath('data.2.name', 'JavaScript');
    }

    public function test_global_search_categories(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?search=java');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'JavaScript');
    }

    public function test_categories_have_articles_count(): void
    {
        $response = $this->getJson(self::ENDPOINT);

        $response->assertOk();

        $laravelCategory = collect($response->json('data'))
            ->firstWhere('name', 'Laravel');

        $this->assertEquals(5, $laravelCategory['articles_count']);
    }
}
