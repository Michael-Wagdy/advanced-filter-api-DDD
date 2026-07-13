# Advanced Filter API

A production-grade Laravel 13 application implementing an Advanced Filtering System using strict Domain-Driven Design (DDD), a `FilterableBuilder` query layer, and modular architecture via `nwidart/laravel-modules`.

Two modules:

- **Blog** — Articles, Categories, Tags with relationships and list APIs.
- **Shared** — Reusable `FilterableBuilder`, query builders, middleware, and cross-cutting infrastructure used by all modules.

---

## Architectural Layout

### DDD Layer Separation

```
Modules/Shared/                               ← Shared Infrastructure
├── app/
│   ├── Http/Middleware/                      ← Cross-cutting concerns (PerformanceTelemetry)
│   └── Infrastructure/QueryBuilders/
│       └── FilterableBuilder.php             ← Base query builder with JOIN-based filtering
└── Providers/                                ← Module service provider

Modules/Blog/                                 ← Domain Module
├── app/
│   ├── Http/                                 ← Application Layer
│   │   ├── Controllers/Blog/                 ← Orchestration only (uses Form Requests)
│   │   ├── Requests/                         ← Input validation (FilterArticlesRequest, etc.)
│   │   └── Resources/                       ← Outbound JSON transformation
│   ├── Domain/                               ← Domain Layer
│   │   ├── DTOs/                             ← FilterResult DTO (boundary object)
│   │   ├── Services/                         ← Business logic orchestration
│   │   └── Repositories/                     ← Abstract contracts (interfaces)
│   └── Infrastructure/                       ← Infrastructure Layer
│       └── Eloquent/
│           ├── Models/                       ← Skinny Eloquent models (override newEloquentBuilder)
│           ├── QueryBuilders/                ← Model-specific query builders
│           └── Repositories/                 ← Concrete query implementations
├── database/
│   ├── factories/                            ← SRP: one factory per entity
│   ├── migrations/                           ← Schema + composite/fulltext indexes
│   └── seeders/                              ← High-speed bulk insert seeder
└── tests/Feature/                            ← Integration tests
```

### FilterableBuilder Architecture

Requests flow through model-specific query builders that extend `FilterableBuilder`:

```
HTTP Request → Controller (Form Request validates input)
  → Domain Service → Repository → FilterableBuilder chain:
    ┌──────────────────────┐
    │ FilterableBuilder     │  ← Base: JOIN-based filtering, search, sort
    │   ├── whereFieldFilters()      ← Direct column filters
    │   ├── whereRelationFilters()   ← Nested/dot-notation relation JOINs
    │   ├── whereSearch()            ← Full-text (MySQL) or LIKE fallback (SQLite)
    │   └── applySort()             ← Directional sorting
    └──────────────────────┘
    │
    ▼
  FilterResult DTO → Controller → JSON Response
```

Each model overrides `newEloquentBuilder()` to return its specific query builder:

```php
// Article model
public function newEloquentBuilder($query): Builder
{
    return new ArticleQueryBuilder($query);
}
```

### Supported Filter Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `like` | Text matching (LIKE val%) | `filter[title][like]=laravel` |
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
-- Articles table: optimized for the most common query patterns
INDEX idx_status_created    ON articles (status, created_at)   -- status filter + date sort
INDEX idx_category_status   ON articles (category_id, status)  -- category filter + status filter
INDEX idx_user_created      ON articles (user_id, created_at)  -- author filter + date sort
INDEX idx_view_count        ON articles (view_count)           -- numeric range filters

-- Pivot table: primary key prevents duplicates in JOINs
PRIMARY KEY (article_id, tag_id) ON article_tag

-- Comments table
INDEX idx_article_created   ON comments (article_id, created_at) -- article's comments + date sort
INDEX idx_user_id           ON comments (user_id)               -- comment author filter
```

**Why these indexes matter:**

- `status + created_at`: When filtering `status=published` and sorting by date, MySQL can satisfy both from the index without touching the table.
- `category_id + status`: The most common admin query — "show me published articles in this category."
- `article_tag` composite PK: Prevents duplicate tag assignments and makes BelongsToMany JOINs efficient.
- `view_count`: Numeric range filters (`gt`, `lt`) benefit from B-tree index lookups.

**Full-Text Indexes** (MySQL/MariaDB):

```sql
ALTER TABLE articles ADD FULLTEXT INDEX ft_articles_title_body (title, body);
```

SQLite environments automatically fall back to `LIKE '%term%'` in the `FilterableBuilder::whereSearch()` method.

### Pagination Protection

- Maximum `per_page` capped at 100 to prevent unbounded memory allocation
- Default pagination at 15 records per page
- All queries use `LengthAwarePaginator` for efficient offset-based pagination
- Count queries use the same indexed paths as the data query (no separate full table scan)

### Eager Loading Strategy

Every repository explicitly eager-loads only the relationship subsets needed for the response payload, avoiding N+1 queries:

```php
// ArticleEloquentRepository — loads relationships used in ArticleResource
$query->with(['category', 'tags']);

// CategoryEloquentRepository — uses withCount for the articles_count field
$query->withCount('articles');

// TagEloquentRepository
$query->withCount('articles');
```

**Key design decisions:**

- `with()` is used when the full relationship data is serialized in the response (Article → Category, Article → Tags).
- `withCount()` is used when only the count is needed (Category → articles_count, Tag → articles_count), avoiding loading thousands of related models into memory.
- Comments are NOT eager-loaded on the article list endpoint because they're only used for filtering (via JOINs), not displayed in the list response.

### Query Builder JOIN Strategy

When relationship filters are applied (e.g., `filter[category][slug][eq]=laravel`), the `FilterableBuilder` performs explicit SQL JOINs instead of using subqueries:

```sql
-- filter[category][slug][eq]=laravel generates:
SELECT DISTINCT articles.*
FROM articles
INNER JOIN categories ON categories.id = articles.category_id
WHERE categories.slug = 'laravel'
```

```sql
-- filter[comments][user][name][like]=John generates:
SELECT DISTINCT articles.*
FROM articles
INNER JOIN comments ON comments.article_id = articles.id
INNER JOIN users ON users.id = comments.user_id
WHERE users.name LIKE '%John%'
```

The `DISTINCT` keyword prevents duplicate rows when a BelongsToMany JOIN (e.g., tags) matches multiple pivot records for the same article.

### Scaling Considerations

| Scale | Strategy |
|-------|----------|
| < 100K rows | Current Eloquent approach is optimal. Composite indexes handle all query patterns. |
| 100K–1M rows | Add read replicas. Route all filter queries to a replica. Keep writes on primary. |
| 1M–10M rows | Introduce Elasticsearch for full-text search. Keep relational filters on MySQL with optimized indexes. |
| 10M+ rows | Full Elasticsearch migration. Move complex nested filters to ES nested queries. Use async indexing via Redis queue. |

**At each scale threshold, the repository interface stays the same** — only the concrete implementation changes (Eloquent → Elasticsearch).

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

## API Usage

All endpoints accept the same query parameter structure. Base URL: `/api/v1/blog`

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/blog/articles` | List articles with filters |
| `GET` | `/api/v1/blog/categories` | List categories with filters |
| `GET` | `/api/v1/blog/tags` | List tags with filters |

Full request examples (filtering, search, sorting, pagination, relationship filters, combined queries) are in [`api-tests.http`](api-tests.http) — open it in any HTTP client (VS Code REST Client, JetBrains, Insomnia) and run requests directly.

---

## Design Notes

### Why Not Full Polymorphism?

This implementation uses standard Eloquent relationships (`BelongsTo`, `HasMany`, `BelongsToMany`)
for Article ↔ Category, Article ↔ Tag, and Article ↔ Comment. The natural next step would be
to create additional domain modules — for example `Modules/Shop` with Books or Products — and
make Category, Tag, and Comment fully polymorphic (`categorizable`, `taggable`, `commentable`)
so they can serve any module. However, to reduce complexity while still meeting the assessment
requirements, I chose to consolidate the shared infrastructure into a `Modules/Shared` module
instead of spinning up extra domain modules.

If the system needed to support Books, Courses, or other content types in the future, the
following would become morphable:

- **Tags** → `taggable_type` + `taggable_id` — Tags can belong to Articles, Books, etc.
- **Categories** → `categorizable_type` + `categorizable_id` — Categories can classify anything.
- **Comments** → `commentable_type` + `commentable_id` — Comments on Articles, Books, etc.

The pipeline architecture (`FieldFilter → RelationFilter → SearchFilter → SortFilter`) is
designed to work unchanged with polymorphic relations — only the model relationships and
migrations would need updating.

### Module Boundaries

The **Shared** module exists so that filter pipes and middleware are not duplicated when
new domain modules are added. Any future module (e.g., `Modules/Shop`) can import the
same `FieldFilter`, `RelationFilter`, etc. without depending on the Blog module.

---

## Testing

```bash
# Run the full test suite
php artisan test --testsuite=Blog

# Tests cover:
# - Direct field filtering (eq, like, gt, gte, lt, lte, neq, empty, filled, in)
# - Relationship filtering (category.slug, tags.name, user.name)
# - Deep nested relationship filtering (comments.user.name)
# - Full-text global search
# - Sorting (asc/desc)
# - Pagination
# - Combined multi-condition filters
# - Edge cases (empty/null checks, IN with single value, date boundaries)
```

All tests execute against an in-memory SQLite database via `RefreshDatabase`.
