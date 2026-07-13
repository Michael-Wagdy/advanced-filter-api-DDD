<?php

namespace Modules\Blog\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Infrastructure\Eloquent\Models\Article;
use Modules\Blog\Infrastructure\Eloquent\Models\Category;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'excerpt' => fake()->paragraph(1),
            'body' => fake()->paragraphs(5, true),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'view_count' => fake()->numberBetween(0, 10000),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => 'published']);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function rawDefinition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'excerpt' => fake()->paragraph(1),
            'body' => fake()->paragraphs(5, true),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'view_count' => fake()->numberBetween(0, 10000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
