<?php

namespace Modules\Blog\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Infrastructure\Eloquent\Models\Article;
use Modules\Blog\Infrastructure\Eloquent\Models\Comment;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(1),
        ];
    }

    public function rawDefinition(int $articleId, int $userId): array
    {
        return [
            'article_id' => $articleId,
            'user_id' => $userId,
            'body' => fake()->paragraph(1),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
