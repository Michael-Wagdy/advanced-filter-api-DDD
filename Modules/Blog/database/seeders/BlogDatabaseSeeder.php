<?php

namespace Modules\Blog\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BlogDatabaseSeeder extends Seeder
{
    private const USER_COUNT = 50;
    private const CATEGORY_COUNT = 50;
    private const TAG_COUNT = 200;
    private const ARTICLE_COUNT = 120_000;
    private const TAGS_PER_ARTICLE_MIN = 1;
    private const TAGS_PER_ARTICLE_MAX = 5;
    private const COMMENTS_PER_ARTICLE_CHANCE = 0.3;
    private const COMMENTS_PER_ARTICLE_MAX = 5;
    private const CHUNK_SIZE = 5000;

    public function run(): void
    {
        $this->command?->info('Seeding users...');
        $userIds = $this->seedUsers();

        $this->command?->info('Seeding categories...');
        $categoryIds = $this->seedCategories();

        $this->command?->info('Seeding tags...');
        $tagIds = $this->seedTags();

        $this->command?->info('Seeding ' . number_format(self::ARTICLE_COUNT) . ' articles...');
        $articleIds = $this->seedArticles($userIds, $categoryIds);

        $this->command?->info('Seeding article-tag pivots...');
        $this->seedArticleTagPivot($articleIds, $tagIds);

        $this->command?->info('Seeding comments...');
        $this->seedComments($articleIds, $userIds);

        $this->command?->info('Seeding complete.');
    }

    private function seedUsers(): array
    {
        $users = [];
        for ($i = 0; $i < self::USER_COUNT; $i++) {
            $users[] = [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('users')->insert($users);

        return DB::table('users')->pluck('id')->toArray();
    }

    private function seedCategories(): array
    {
        $categories = [];
        $slugs = [];
        for ($i = 0; $i < self::CATEGORY_COUNT; $i++) {
            $name = fake()->unique()->words(2, true);
            $slug = str($name)->slug()->toString();
            if (in_array($slug, $slugs)) {
                $slug = $slug . '-' . $i;
            }
            $slugs[] = $slug;

            $categories[] = [
                'name' => ucfirst($name),
                'slug' => $slug,
                'description' => fake()->paragraph(1),
                'is_active' => fake()->boolean(85),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('categories')->insert($categories);

        return DB::table('categories')->pluck('id')->toArray();
    }

    private function seedTags(): array
    {
        $tags = [];
        $slugs = [];
        for ($i = 0; $i < self::TAG_COUNT; $i++) {
            $name = fake()->unique()->words(2, true);
            $slug = str($name)->slug()->toString() . '-' . $i;
            $slugs[] = $slug;

            $tags[] = [
                'name' => ucfirst($name),
                'slug' => $slug,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('tags')->insert($tags);

        return DB::table('tags')->pluck('id')->toArray();
    }

    private function seedArticles(array $userIds, array $categoryIds): array
    {
        $allArticleIds = [];
        $statusOptions = ['draft', 'published', 'archived'];
        $batch = [];
        $total = self::ARTICLE_COUNT;
        $inserted = 0;

        for ($i = 1; $i <= $total; $i++) {
            $title = fake()->sentence(4);
            $slug = str($title)->slug()->toString() . '-' . $i;
            $now = now()->toDateTimeString();

            $batch[] = [
                'user_id' => $userIds[array_rand($userIds)],
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'title' => $title,
                'slug' => $slug,
                'excerpt' => fake()->paragraph(1),
                'body' => fake()->paragraphs(5, true),
                'status' => $statusOptions[array_rand($statusOptions)],
                'view_count' => fake()->numberBetween(0, 10000),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= self::CHUNK_SIZE) {
                DB::table('articles')->insert($batch);
                $inserted += count($batch);

                if ($inserted % 10000 === 0) {
                    $this->command?->info("  Inserted " . number_format($inserted) . " / " . number_format($total) . " articles");
                }

                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('articles')->insert($batch);
            $inserted += count($batch);
        }

        $this->command?->info("  Total articles inserted: " . number_format($inserted));

        return DB::table('articles')->pluck('id')->toArray();
    }

    private function seedArticleTagPivot(array $articleIds, array $tagIds): void
    {
        $batch = [];
        $articleCount = count($articleIds);

        for ($i = 0; $i < $articleCount; $i++) {
            $tagCount = random_int(self::TAGS_PER_ARTICLE_MIN, self::TAGS_PER_ARTICLE_MAX);
            $selectedTags = [];

            for ($j = 0; $j < $tagCount; $j++) {
                $tagId = $tagIds[array_rand($tagIds)];
                $selectedTags[$tagId] = true;
            }

            foreach (array_keys($selectedTags) as $tagId) {
                $batch[] = [
                    'article_id' => $articleIds[$i],
                    'tag_id' => $tagId,
                ];
            }

            if (count($batch) >= self::CHUNK_SIZE) {
                DB::table('article_tag')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('article_tag')->insert($batch);
        }
    }

    private function seedComments(array $articleIds, array $userIds): void
    {
        $batch = [];
        $articleCount = count($articleIds);

        for ($i = 0; $i < $articleCount; $i++) {
            if (mt_rand(1, 100) / 100 > self::COMMENTS_PER_ARTICLE_CHANCE) {
                continue;
            }

            $commentCount = random_int(1, self::COMMENTS_PER_ARTICLE_MAX);
            $now = now()->toDateTimeString();

            for ($j = 0; $j < $commentCount; $j++) {
                $batch[] = [
                    'article_id' => $articleIds[$i],
                    'user_id' => $userIds[array_rand($userIds)],
                    'body' => fake()->paragraph(1),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($batch) >= self::CHUNK_SIZE) {
                DB::table('comments')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('comments')->insert($batch);
        }
    }
}
