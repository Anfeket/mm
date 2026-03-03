# CLAUDE.md

## Project Overview

This is **mm** — a meme-sharing / imageboard-style web application built with **Laravel** (PHP) and **Blade** templates. Users can upload images and videos, tag them with categorized tags, vote on posts, and comment. The app uses an invite-only registration system.

## Tech Stack

- **Backend:** PHP / Laravel (latest)
- **Frontend:** Blade templates, vanilla JavaScript and CSS (no frontend framework)
- **CSS:** Custom CSS with variables — no utility framework. Split across `base.css`, `layout.css`, `pages.css`, `components.css`. Tag colors use `--color-tag-{category}` CSS variables.
- **Build Tool:** Vite (via `laravel-vite-plugin`)
- **Database:** MySQL/MariaDB (or SQLite for local dev) — managed via Laravel migrations
- **Media Processing:** FFmpeg (self-installed binary in `storage/bin/`)
- **Static Analysis:** PHPStan
- **Testing:** Pest

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
- `php artisan test` — Run Pest test suite
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
tests/                # Pest tests
```

## Key Architectural Patterns

- **Service classes** (`app/Services/`) encapsulate business logic — prefer adding logic there over controllers.
- **Queued jobs** (`app/Jobs/`) handle heavy processing (media transcoding, avatar cropping) asynchronously.
- **Hash-based file storage:** Uploaded files are stored by content hash (`xx/xx/hash.ext`) to deduplicate.
- **Tag system:** Tags have 9 categories defined in the `TagCategory` enum:
  - `artist` (prefix `a:`), `copyright` (`c:`), `origin` (`o:`), `format` (`f:`), `template` (`t:`), `general` (`g:`), `usage` (`u:`), `meta` (`m:`), `subject` (`s:`)
  - Both short (`a:`) and long (`artist:`) prefix forms are supported
  - Tag names are normalized: lowercase, spaces→underscores, allowed chars `[a-z0-9_():-]`
  - Always use `TagService` for parsing/normalizing — never manually
- **Invite-only registration:** Users need a valid 32-char invite code to register. Invites are created by existing users.
- **Posts are unlisted by default** after upload and go through a processing pipeline.
- **Notifications:** Laravel database notifications are set up (`AvatarProcessed`). Use `$user->notify(new SomeNotification())` — do not build a custom notifications table.
- **Tag aliases:** Tags may alias another tag within the same category. Tags have a composite unique key `(name, category)` — the same name can exist across different categories (e.g. `g:instagram` and `o:instagram` are distinct).

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
- Some routes are still placeholders - check `routes/web.php` for the current route definitions.
- File uploads max size is 100MB. Supported types: JPEG, PNG, GIF, WebP, MP4, WebM.
- Tag parsing supports category prefixes (`category:name`) — always use `TagService` for parsing/normalizing tags, never do it manually.
- New posts have `is_listed = false` and `processing_status = Processing` by default — they become visible after processing completes.
- Duplicate detection is hash-based — two identical files will be rejected.
- The autocomplete endpoint `GET /tags/autocomplete?q=` is public and returns JSON. Frontend autocomplete lives in `resources/js/search.js`.

## Testing

- Tests live in `tests/`.
- Tests use **Pest** with a file-based SQLite database at `database/testing.sqlite` for speed — no full `RefreshDatabase` wipe between runs.
- Run with: `php artisan test`
- Use `Post::factory()->fake()` state in tests to skip downloading images from picsum.photos.
- When adding new features, include matching test coverage.
- Use PHPStan for static analysis before committing.
