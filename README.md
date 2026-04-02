# Topping Africa

African news, entertainment, business, and culture magazine built with Laravel 12.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Livewire 3, Alpine.js, Tailwind CSS |
| Build | Vite |
| Database | MySQL |
| Media | Spatie Media Library (AWS S3) |
| Auth | Laravel Breeze, Spatie Permission |
| Editor | TipTap WYSIWYG |
| AI | OpenAI, Perplexity, Anthropic Claude |

## Features

### Content Management
- Blog posts with multiple types (article, video, quiz, trivia, listicle, gallery)
- Categories with hierarchy and tags
- TipTap rich text editor with image upload to S3
- Media library with Pexels stock photo integration
- Static pages management
- Comments with nested replies and moderation
- RSS feed and XML sitemap

### AI Tools
- AI blog post generator (OpenAI GPT-4o, Perplexity sonar-pro, Claude)
- Title suggestion engine with niche-based trending topics
- Auto-generated SEO metadata, social sharing text, and internal links

### SEO Intelligence
- 5-category SEO analyzer (Content Quality, Technical SEO, Readability, User Engagement, On-Page Elements)
- Weighted scoring (0-100) with letter grades (A+ to F)
- AI-powered "Apply Recommendations" that rewrites content for optimal SEO
- JSON-LD structured data (Organization, Article, BreadcrumbList, WebSite)
- Open Graph and Twitter Card meta tags
- Canonical URLs and security headers

### Admin Panel
- Dashboard with stats overview
- Post editor with AI generation and SEO analysis
- Category, tag, and page management
- User and role management (super-admin, editor, author)
- Comment moderation
- Newsletter subscriber management with CSV export
- Ad management with scheduled placements
- Site settings

### Frontend
- Magazine-style responsive design
- Dark/light mode with localStorage persistence
- Sticky header with category navigation
- Mobile menu and search overlay
- Scroll progress indicator
- Ad slot placements (header, in-article, after-content, footer)

## Installation

```bash
# Clone
git clone https://github.com/sargbah84/toppingafrica.git
cd toppingafrica

# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Configure .env with:
# - Database credentials
# - AWS S3 credentials
# - AI API keys (OPENAI_API_KEY, PERPLEXITY_API_KEY, ANTHROPIC_API_KEY)
# - PEXELS_API_KEY (for stock photos)

# Database
php artisan migrate --seed

# Build assets
npm run build

# Serve
php artisan serve
```

## Default Admin Login

- **Email:** admin@toppingafrica.com
- **Password:** password

## Artisan Commands

```bash
# Migrate data from old Botble CMS database
php artisan migrate:botble --fresh

# Import existing S3 images into media library
php artisan media:import-s3 --fresh
```

## License

Proprietary - All rights reserved.
