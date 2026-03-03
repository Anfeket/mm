# CLAUDE.md

## Project Overview

This is **mm** — a media-sharing / imageboard-style web application built with **Laravel** (PHP) and **Blade** templates. Users can upload images and videos, tag them with categorized tags, vote on posts, and comment. The app uses an invite-only registration system.

## Tech Stack

- **Backend:** PHP / Laravel (latest)
- **Frontend:** Blade templates, Tailwind CSS v4, vanilla JavaScript
- **Build Tool:** Vite (via `laravel-vite-plugin`)
- **Database:** MySQL/MariaDB (or SQLite for local dev) — managed via Laravel migrations
- **Media Processing:** FFmpeg (self-installed binary in `storage/bin/`)
- **Static Analysis:** PHPStan
- **Testing:** PHPUnit

## Commands

### Development
- `composer run dev` — Start all dev services at once (Laravel server, queue listener, and Vite)
  - Runs: `php artisan serve`, `php artisan queue:listen`, and `npm run dev` concurrently

### Individual Services
- `php artisan serve` — Start the local dev server
- `npm run dev` — Start Vite dev server (CSS/JS hot reload)
- `php artisan queue:listen` — Process queued jobs

### Build & Test
- `npm run build` — Build frontend assets for production
- `php artisan test` — Run PHPUnit test suite
- `php artisan migrate` — Run database migrations
- `./vendor/bin/phpstan analyse` — Run static analysis

## Project Structure

```
app/
├── Console/          # Artisan commands
├── Http/
│   └── Controllers/  # Route controllers
│       ├── AuthController.php      # Login, register (invite-based), logout
│       ├── PostController.php      # CRUD for media posts, tag-based search
│       ├── ProfileController.php   # User profile, avatar upload, invites
│       ├── VoteController.php      # Upvote/downvote on posts
│       ├── TagController.php       # Tag CRUD and autocomplete API
│       └── CommentController.php   # Comments (scaffold)
├── Jobs/             # Queued jobs (ProcessPostMedia, ProcessAvatar)
├── Listeners/        # Event listeners
├── Models/           # Eloquent models
│   ├── Post.php      # Media post (file, tags, votes, comments)
│   ├── User.php      # User account
│   ├── Tag.php       # Categorized tags
│   ├── Vote.php      # Post votes (+1/-1)
│   ├── Comment.php   # Post comments
│   └── Invite.php    # Invite codes for registration
├── Notifications/
├── Providers/
├── Services/         # Business logic services
│   ├── TagService.php          # Tag parsing, normalization, search
│   ├── FileStorageService.php  # Hash-based file storage
│   └── FfmpegService.php       # FFmpeg binary management & execution
├── View/             # View components / composers
├── PostProcessingStatus.php    # Enum: post processing states
└── TagCategory.php             # Enum: tag categories with prefixes

bootstrap/            # Laravel bootstrap
config/               # Configuration files
database/             # Migrations and seeders
public/               # Public assets and entry point
resources/
├── views/
│   ├── auth/         # Login & register views
│   ├── post/         # Post listing, creation, detail views
│   ├── profile/      # Profile management views
│   └── components/   # Reusable Blade components
routes/
├── web.php           # All web routes
└── console.php       # Console route definitions
storage/              # Uploads, logs, cached views, ffmpeg binary
tests/                # PHPUnit tests
```

## Key Architectural Patterns

- **Service classes** (`app/Services/`) encapsulate business logic — prefer adding logic there over controllers.
- **Queued jobs** (`app/Jobs/`) handle heavy processing (media transcoding, avatar cropping) asynchronously.
- **Hash-based file storage:** Uploaded files are stored by content hash (`xx/xx/hash.ext`) to deduplicate.
- **Tag system:** Tags have categories (defined in `TagCategory` enum) with prefix syntax (e.g., `artist:name`, `meta:tag`). Tag names are normalized to lowercase with underscores.
- **Invite-only registration:** Users need a valid 32-char invite code to register. Invites are created by existing users.
- **Posts are unlisted by default** after upload and go through a processing pipeline.

## Models & Relationships

- `Post` -> belongsTo `User` (author), hasMany `Vote`, hasMany `Comment`, belongsToMany `Tag`
- `User` -> hasMany `Post`, hasMany `Vote`, hasMany `Invite`
- `Tag` -> belongsToMany `Post`, may alias another `Tag`
- `Vote` -> belongsTo `Post`, belongsTo `User` (value is +1 or -1)
- `Invite` -> belongsTo `User` (creator), nullable `used_by` user

## Coding Conventions

- **PHP:** Follow PSR-12 coding style.
- **Naming:** Use `snake_case` for database columns and route names; `camelCase` for PHP variables and methods; `PascalCase` for classes.
- **Controllers:** Keep controllers thin — delegate business logic to Service classes.
- **Enums:** Use PHP 8.1+ backed enums for finite sets (see `TagCategory`, `PostProcessingStatus`).
- **Blade templates:** Use Blade components (`resources/views/components/`) for reusable UI elements.
- **Validation:** Always validate in controllers using Laravel's `$request->validate()`. 
- **No API routes yet** — all routes are web routes defined in `routes/web.php`.

## Common Pitfalls

- FFmpeg binary lives in `storage/bin/`, not globally installed. Use `FfmpegService` to interact with it.
- File uploads max size is 100MB. Supported types: JPEG, PNG, GIF, WebP, MP4, WebM.
- Tag parsing supports category prefixes (`category:name`) — always use `TagService` for parsing/normalizing tags, never do it manually.
- New posts have `is_listed = false` and `processing_status = Processing` by default — they become visible after processing completes.
- Duplicate detection is hash-based — two identical files will be rejected.

## Testing

- Tests live in `tests/`.
- Run with: `php artisan test`
- When adding new features, include matching test coverage.
- Use PHPStan for static analysis before committing.