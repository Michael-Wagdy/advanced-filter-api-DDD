<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\Blog\ArticleController;
use Modules\Blog\Http\Controllers\Blog\CategoryController;
use Modules\Blog\Http\Controllers\Blog\TagController;
use Modules\Shared\Http\Middleware\PerformanceTelemetry;

Route::middleware([PerformanceTelemetry::class])->group(function () {
    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('tags', [TagController::class, 'index']);
});
