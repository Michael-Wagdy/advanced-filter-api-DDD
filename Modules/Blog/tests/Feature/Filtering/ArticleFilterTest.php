<?php

namespace Modules\Blog\Tests\Feature\Filtering;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Infrastructure\Eloquent\Models\Article;
use Modules\Blog\Infrastructure\Eloquent\Models\Category;
use Modules\Blog\Infrastructure\Eloquent\Models\Comment;
use Modules\Blog\Infrastructure\Eloquent\Models\Tag;
use Tests\TestCase;

class ArticleFilterTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'api/v1/blog/articles';

    protected User $user;
    protected Category $category;
    protected array $tags = [];
    protected array $articles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->category = Category::factory()->create(['slug' => 'laravel']);

        $this->tags[] = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
        $this->tags[] = Tag::factory()->create(['name' => 'Eloquent', 'slug' => 'eloquent']);
        $this->tags[] = Tag::factory()->create(['name' => 'Testing', 'slug' => 'testing']);

        $this->articles[] = Article::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Laravel Advanced Filtering Guide',
            'slug' => 'laravel-advanced-filtering-guide',
            'status' => 'published',
            'view_count' => 1500,
        ]);

        $this->articles[0]->tags()->attach([$this->tags[0]->id, $this->tags[1]->id]);

        $this->articles[] = Article::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'PHP Testing Best Practices',
            'slug' => 'php-testing-best-practices',
            'status' => 'published',
            'view_count' => 3200,
        ]);

        $this->articles[1]->tags()->attach([$this->tags[0]->id, $this->tags[2]->id]);

        $this->articles[] = Article::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => Category::factory()->create(['slug' => 'javascript'])->id,
            'title' => 'JavaScript Fundamentals',
            'slug' => 'javascript-fundamentals',
            'status' => 'draft',
            'view_count' => 100,
        ]);

        $commentAuthor = User::factory()->create(['name' => 'John Commenter']);
        Comment::factory()->create([
            'article_id' => $this->articles[0]->id,
            'user_id' => $commentAuthor->id,
            'body' => 'Great article on filtering!',
        ]);
    }

    public function test_articles_list_returns_paginated_results(): void
    {
        $response = $this->getJson(self::ENDPOINT);

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'slug', 'status', 'view_count', 'category', 'tags'],
                ],
            ]);
    }

    public function test_filter_articles_by_exact_status(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[status][eq]=published');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filter_articles_by_like_title(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[title][like]=Laravel');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Advanced Filtering Guide');
    }

    public function test_filter_articles_by_view_count_gt(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[view_count][gt]=1000');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filter_articles_by_view_count_between(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[view_count][gte]=1500&filter[view_count][lte]=3500');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filter_articles_by_relation_category_slug(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[category][slug][eq]=laravel');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filter_articles_by_relation_tag_name(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[tags][name][like]=Testing');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'PHP Testing Best Practices');
    }

    public function test_filter_articles_by_nested_comment_author(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[comments][user][name][like]=John');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Advanced Filtering Guide');
    }

    public function test_sort_articles_descending(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?sort=-view_count');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'PHP Testing Best Practices')
            ->assertJsonPath('data.1.title', 'Laravel Advanced Filtering Guide');
    }

    public function test_sort_articles_ascending(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?sort=view_count');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'JavaScript Fundamentals');
    }

    public function test_global_search_articles(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?search=filtering');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Advanced Filtering Guide');
    }

    public function test_pagination_per_page(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?per_page=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_empty_status_filter_returns_no_results(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[status][eq]=archived');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_combined_filters(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[status][eq]=published&filter[view_count][gt]=2000&sort=-view_count');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'PHP Testing Best Practices');
    }
}
