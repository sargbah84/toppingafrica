# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Topping Africa is an African news/content platform rebuilt with Laravel 12. It features a CMS with AI-powered content generation, SEO intelligence scoring, and a magazine-style public frontend.

## Common Commands

```bash
# Full project setup (install deps, generate key, migrate, build assets)
composer setup

# Development (runs artisan serve, queue:listen, pail, npm dev concurrently)
composer dev

# Individual dev servers
php artisan serve          # Laravel app at localhost:8000
npm run dev                # Vite HMR dev server

# Build frontend assets
npm run build

# Run tests (clears config cache first)
composer test
php artisan test                    # all tests
php artisan test --filter=TestName  # single test

# Code style
./vendor/bin/pint          # Laravel Pint (auto-fix)

# Database
php artisan migrate
php artisan db:seed        # seeds permissions, roles, admin user, default categories

# Custom commands
php artisan migrate:botble [--fresh]     # migrate data from old Botble CMS
php artisan media:import-s3 [--fresh]    # import S3 images into media library
```

## Architecture

### Stack
- **Backend:** Laravel 12, PHP 8.2+, Livewire 3 + Volt
- **Frontend:** Blade templates, Alpine.js, Tailwind CSS 3, TipTap editor
- **Database:** SQLite (dev) / MySQL (prod), in-memory SQLite for tests
- **Auth:** Laravel Breeze + Spatie Permission (roles: super-admin, editor, author)
- **Media:** Spatie Media Library with S3 storage
- **AI:** OpenAI (gpt-4o), Perplexity (sonar-pro), Anthropic (claude-sonnet) via service classes

### Key Directories

- `app/Services/AI/` — AI blog generation services (OpenAI, Perplexity)
- `app/Services/Blog/` — Post generation orchestration, social sharing, reading time, internal links
- `app/Services/Blog/Seo/` — 5 weighted SEO analyzers (Content, Technical, Readability, Engagement, OnPage)
- `app/Livewire/Admin/Blog/` — Post editor, AI generator, SEO panel, media library (main admin UI)
- `app/Repositories/` — PostRepository, CategoryRepository for query encapsulation
- `app/DTOs/Blog/` — Data transfer objects for post generation and social sharing
- `config/blog.php` — Post types, AI provider settings, default categories, generation lengths
- `config/seo-intelligence.php` — SEO scoring weights, grade thresholds, target metrics
- `routes/admin.php` — All admin routes (prefixed `/admin`, requires `is_staff` middleware)
- `routes/web.php` — Public routes; `/{slug}` is a catch-all for posts (must be last route)

### Routing Pattern
Admin routes are in `routes/admin.php`, protected by `IsStaff` middleware. Public blog uses `/{slug}` as a catch-all route in `routes/web.php` — any new public routes must be defined before it.

### Post Types
Posts support multiple types defined in `config/blog.php`: article, video, quiz, trivia, listicle, gallery.

### SEO Intelligence
Five analyzer classes in `app/Services/Blog/Seo/` each score a category (0-100) with configurable weights summing to 100%. Weights and thresholds are in `config/seo-intelligence.php`.

### Middleware
- `IsStaff` — gates admin routes, checks `is_staff` or `is_super_admin` on User
- `SecurityHeaders` — adds HSTS, X-Frame-Options, etc. to responses

### Testing
PHPUnit 11 with SQLite in-memory. Test suites in `tests/Feature/` and `tests/Unit/`. Uses `RefreshDatabase` trait.
