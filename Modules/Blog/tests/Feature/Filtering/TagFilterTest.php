<?php

namespace Modules\Blog\Tests\Feature\Filtering;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Infrastructure\Eloquent\Models\Article;
use Modules\Blog\Infrastructure\Eloquent\Models\Category;
use Modules\Blog\Infrastructure\Eloquent\Models\Tag;
use Tests\TestCase;

class TagFilterTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'api/v1/blog/tags';

    protected User $user;
    protected Category $category;
    protected array $tags = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();

        $this->tags[] = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        $this->tags[] = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
        $this->tags[] = Tag::factory()->create(['name' => 'Eloquent', 'slug' => 'eloquent']);

        $article1 = Article::factory()->create([
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);
        $article1->tags()->attach([$this->tags[0]->id, $this->tags[1]->id]);

        $article2 = Article::factory()->create([
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);
        $article2->tags()->attach([$this->tags[2]->id]);
    }

    public function test_tags_list_returns_paginated_results(): void
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

    public function test_filter_tags_by_exact_name(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[name][eq]=PHP');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'PHP');
    }

    public function test_filter_tags_by_like_name(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[name][like]=Lar');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laravel');
    }

    public function test_sort_tags_by_name(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?sort=name');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Eloquent')
            ->assertJsonPath('data.2.name', 'PHP');
    }

    public function test_global_search_tags(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?search=php');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'PHP');
    }

    public function test_tags_have_articles_count(): void
    {
        $response = $this->getJson(self::ENDPOINT);

        $response->assertOk();

        $laravelTag = collect($response->json('data'))
            ->firstWhere('name', 'Laravel');

        $this->assertEquals(1, $laravelTag['articles_count']);
    }

    public function test_filter_tags_by_slug(): void
    {
        $response = $this->getJson(self::ENDPOINT . '?filter[slug][eq]=eloquent');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Eloquent');
    }

    public function test_filter_tags_by_relation_article_status_nested(): void
    {
        $archivedTag = Tag::factory()->create(['name' => 'Archived Only', 'slug' => 'archived-only']);
        $article = Article::factory()->create([
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
            'status' => 'archived',
        ]);
        $article->tags()->attach([$archivedTag->id]);

        $response = $this->getJson(self::ENDPOINT . '?filter[articles][status][eq]=published');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_filter_tags_by_relation_article_status_dot_notation(): void
    {
        $archivedTag = Tag::factory()->create(['name' => 'Archived Only', 'slug' => 'archived-only']);
        $article = Article::factory()->create([
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
            'status' => 'archived',
        ]);
        $article->tags()->attach([$archivedTag->id]);

        $response = $this->getJson(self::ENDPOINT . '?filter[articles.status][eq]=published');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }
}
