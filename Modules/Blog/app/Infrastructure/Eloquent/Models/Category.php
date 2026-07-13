<?php

namespace Modules\Blog\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Blog\Infrastructure\Eloquent\QueryBuilders\CategoryQueryBuilder;

class Category extends Model
{
    use HasFactory;

    public function newEloquentBuilder($query): Builder
    {
        return new CategoryQueryBuilder($query);
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
