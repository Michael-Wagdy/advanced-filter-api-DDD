<?php

namespace Modules\Blog\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Blog\Infrastructure\Eloquent\QueryBuilders\TagQueryBuilder;

class Tag extends Model
{
    use HasFactory;

    public function newEloquentBuilder($query): Builder
    {
        return new TagQueryBuilder($query);
    }

    protected $fillable = [
        'name',
        'slug',
    ];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tag');
    }
}
