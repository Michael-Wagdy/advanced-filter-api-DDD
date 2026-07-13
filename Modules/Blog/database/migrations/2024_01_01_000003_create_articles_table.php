<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['category_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['view_count']);
        });

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE articles ADD FULLTEXT INDEX ft_articles_title_body (title, body)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
