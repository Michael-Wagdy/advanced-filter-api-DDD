# Advanced Filter API

A production-grade Laravel 13 application implementing an Advanced Filtering System using strict Domain-Driven Design (DDD), the Pipeline pattern, and modular architecture via `nwidart/laravel-modules`.

---

## Architectural Layout

### DDD Layer Separation

```
Modules/Blog/
├── app/
│   ├── Http/                    ← Application Layer
│   │   ├── Controllers/Blog/    ← Orchestration only
│   │   ├── Middleware/           ← Cross-cutting concerns
│   │   └── Resources/           ← Outbound JSON transformation
│   ├── Domain/                  ← Domain Layer
│   │   ├── Services/            ← Business logic orchestration
│   │   └── Repositories/        ← Abstract contracts (interfaces)
│   └── Infrastructure/          ← Infrastructure Layer
│       └── Eloquent/
│           ├── Models/          ← Skinny Eloquent models
│           ├── Repositories/    ← Concrete query implementations
│           └── FilterPipes/     ← Reusable Pipeline filter classes
├── database/
│   ├── factories/               ← SRP: one factory per entity
│   ├── migrations/              ← Schema + composite/fulltext indexes
│   └── seeders/                 ← High-speed bulk insert seeder
└── tests/Feature/               ← Integration tests
```

### Pipeline Filter Architecture

Requests flow through a composable pipeline of filter pipes:

```
HTTP Request → Controller → Domain Service → Repository → Pipeline:
  ┌──────────┐   ┌──────────────┐   ┌────────────┐   ┌─────────────┐   ┌────────┐
  │FieldFilter│ → │RelationFilter│ → │SearchFilter│ → │SortFilter   │ → │ Paginate│
  └──────────┘   └──────────────┘   └────────────┘   └─────────────┘   └────────┘
```

Each pipe receives an `Illuminate\Database\Eloquent\Builder`, applies its transformation, and passes it forward via `Closure $next`.

### Supported Filter Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `like` | Text matching (LIKE %val%) | `filter[title][like]=laravel` |
| `eq` | Exact match | `filter[status][eq]=published` |
| `neq` | Not equal | `filter[status][neq]=draft` |
| `gt` / `gte` | Greater than (or equal) | `filter[view_count][gt]=1000` |
| `lt` / `lte` | Less than (or equal) | `filter[view_count][lte]=5000` |
| `empty` | IS NULL check | `filter[excerpt][empty]=true` |
| `filled` | IS NOT NULL check | `filter[excerpt][filled]=true` |
| `in` | IN clause | `filter[status][in]=published,draft` |
| `search` | Global full-text search | `search=keyword` |
| `sort` | Column sorting (-desc) | `sort=-view_count` |
| `per_page` | Pagination size (max 100) | `per_page=25` |

### Supported Relationship Filters

- **Direct**: `filter[category][slug][eq]=tech`, `filter[tags][name][like]=php`
- **Deep nested**: `filter[comments][user][name][like]=john`

---

## Setup & Seeding

### Installation

```bash
# Clone and install dependencies
composer install
cp .env.example .env
php artisan key:generate

# Run migrations (SQLite for development/testing)
php artisan migrate

# Seed 120,000+ articles with bulk inserts
php artisan db:seed --class=Modules\\Blog\\Database\\Seeders\\BlogDatabaseSeeder
```

### Running Tests

```bash
# Run all Blog module tests (uses in-memory SQLite)
php artisan test --testsuite=Blog

# Run specific test class
php artisan test --filter=ArticleFilterTest
```

### High-Speed Seeder

The `BlogDatabaseSeeder` generates:
- **50** Users
- **50** Categories
- **200** Tags
- **120,000+** Articles with associated pivot records and comments

**Performance approach**: Uses `DB::table()->insert()` with 5,000-record chunks to avoid memory exhaustion. No `factory()->create()` calls in loops. Raw data arrays are built in-memory and flushed in bulk.

```bash
php artisan db:seed --class=Modules\\Blog\\Database\\Seeders\\BlogDatabaseSeeder
```

---

## Performance Considerations

### Database Schema Optimization

**Composite Indexes** (prevent full table scans on common filter combinations):

```sql
-- Articles table
INDEX idx_status_created    ON articles (status, created_at)
INDEX idx_category_status   ON articles (category_id, status)
INDEX idx_user_created      ON articles (user_id, created_at)
INDEX idx_view_count        ON articles (view_count)

-- Pivot table
PRIMARY KEY (article_id, tag_id) ON article_tag

-- Comments table
INDEX idx_article_created   ON comments (article_id, created_at)
INDEX idx_user_id           ON comments (user_id)
```

**Full-Text Indexes** (MySQL/MariaDB):

```sql
ALTER TABLE articles ADD FULLTEXT INDEX ft_articles_title_body (title, body);
```

SQLite environments automatically fall back to `LIKE '%term%'` in the `SearchFilter`.

### Pagination Protection

- Maximum `per_page` capped at 100 to prevent unbounded memory allocation
- Default pagination at 15 records per page
- All queries use `LENGTHAwarePaginator` for efficient offset-based pagination

### Eager Loading

Every repository explicitly eager-loads only the relationship subsets needed:

```php
// ArticleEloquentRepository
$query->with(['category', 'tags']);

// CategoryEloquentRepository  
$query->withCount('articles');

// TagEloquentRepository
$query->withCount('articles');
```

### Performance Telemetry Middleware

When `APP_DEBUG=true`, the `PerformanceTelemetry` middleware attaches these headers:

| Header | Description |
|--------|-------------|
| `X-Request-Time` | Total request wall-clock time |
| `X-Db-Query-Count` | Number of SQL queries executed |
| `X-Db-Query-Time` | Cumulative SQL execution time |
| `X-Memory-Used` | Memory allocated during request |
| `X-Memory-Peak` | Peak memory usage |

When `APP_DEBUG=false`, the middleware is a no-op passthrough with zero overhead.

---

## Enterprise Scale Strategy (Elasticsearch)

### Problem Statement

At scale (10M+ articles), MySQL/MariaDB full-text search becomes a bottleneck. LIKE queries degrade, full-text index rebuilds block writes, and complex nested relationship filters require expensive JOINs.

### Architecture Blueprint

```
┌─────────────┐     ┌───────────┐     ┌──────────────────┐
│  API Request │────▶│  Laravel   │────▶│  MySQL (write)   │
│              │     │  App       │     │  - Articles      │
│              │     │            │     │  - Categories    │
│              │     │            │     │  - Tags          │
└─────────────┘     └─────┬─────┘     └────────┬─────────┘
                          │                      │
                          │                      │ Model Observer
                          │                      ▼
                          │              ┌──────────────────┐
                          │              │  Redis Queue      │
                          │              │  (Index Jobs)     │
                          └──────▶       └────────┬─────────┘
                                                  │
                                                  ▼
                                         ┌──────────────────┐
                                         │  Elasticsearch   │
                                         │  / OpenSearch    │
                                         │  - Full-text     │
                                         │  - Aggregations  │
                                         │  - Nested joins  │
                                         └──────────────────┘
```

### Implementation Strategy

1. **Infrastructure Layer Swap**: Create an `ElasticsearchArticleRepository` implementing `ArticleRepositoryInterface`. The domain service and controller remain unchanged.

2. **Index Synchronization**: Use Eloquent Observers + Queue Jobs:
   ```php
   class ArticleObserver
   {
       public function created(Article $article): void
       {
           IndexArticleJob::dispatch($article);
       }

       public function updated(Article $article): void
       {
           ReindexArticleJob::dispatch($article);
       }

       public function deleted(Article $article): void
       {
           RemoveArticleFromIndexJob::dispatch($article);
       }
   }
   ```

3. **Async Processing**: Queue jobs via Redis/SQS with retry logic and dead-letter queues. Index operations never block user-facing request threads.

4. **Search Pipeline**: Translate the `filter[field][operator]=value` array into Elasticsearch DSL queries (`bool` → `must`/`filter`/`should` clauses) inside the repository.

5. **Gradual Migration**: Use a feature flag to route read traffic:
   ```php
   $this->app->bind(ArticleRepositoryInterface::class, function () {
       return config('search.driver') === 'elasticsearch'
           ? app(ElasticsearchArticleRepository::class)
           : app(ArticleEloquentRepository::class);
   });
   ```

6. **Consistency**: For user-facing consistency, implement a "read-after-write" guarantee by checking a `search_index_version` column or using Elasticsearch's refresh interval tuning.

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/blog/articles` | List articles with filters |
| `GET` | `/api/blog/categories` | List categories with filters |
| `GET` | `/api/blog/tags` | List tags with filters |

---

## Testing

```bash
# Run the full test suite
php artisan test --testsuite=Blog

# Tests cover:
# - Direct field filtering (eq, like, gt, gte, lt, lte, empty, filled, in)
# - Relationship filtering (category.slug, tags.name, user.name)
# - Deep nested relationship filtering (comments.user.name)
# - Full-text global search
# - Sorting (asc/desc)
# - Pagination
# - Combined multi-condition filters
```

All tests execute against an in-memory SQLite database via `RefreshDatabase`.
