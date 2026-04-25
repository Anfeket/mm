# CLAUDE.md

## Project Overview

This is **mm** — a meme-sharing / imageboard-style web application built with **Laravel** (PHP) and **Blade** templates. Users can upload images and videos, tag them with categorized tags, vote on posts, and comment. The app uses an invite-only registration system.

## Tech Stack

- **Backend:** PHP / Laravel 13
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
│       ├── AccountController.php   # Account settings, avatar upload, invites
│       ├── UserController.php      # Public user profiles
│       ├── VoteController.php      # Upvote/downvote on posts
│       ├── TagController.php       # Tag CRUD and autocomplete API
│       ├── FavoriteController.php  # Toggle favorite status on posts for users
│       └── PostTagController.php   # Attach/detach tags to posts
│       └── CommentController.php   # Comments (store with rate limiting, destroy)
├── Console/
│   └── Commands/
│       └── FfmpegInstall.php       # Artisan command: download & install ffmpeg binary
├── Jobs/             # Queued jobs (ProcessPostMedia, ProcessAvatar)
├── Listeners/
│   └── UpdateFieldsOnLogin.php    # Records last_login_at and last_login_ip on auth
├── Models/           # Eloquent models
│   ├── Post.php      # Media post (file, tags, votes, comments)
│   ├── User.php      # User account
│   ├── Tag.php       # Categorized tags
│   ├── Vote.php      # Post votes (+1/-1)
│   ├── Comment.php   # Post comments
│   └── Invite.php    # Invite codes for registration
├── Notifications/
│   └── AvatarProcessed.php        # Database notification sent after avatar job finishes
├── Providers/
├── Services/         # Business logic services
│   ├── TagService.php          # Tag parsing, normalization, search
│   ├── FileStorageService.php  # Hash-based file storage
│   ├── DiscordService.php      # Discord webhook notifications
│   └── FfmpegService.php       # FFmpeg binary management & execution
├── View/
│   └── Components/
│       ├── Layout.php      # <x-layout> wrapper component
│       └── PostCard.php    # <x-post-card> component
├── Support/
│   └── JsonLd.php       # Helper for generating JSON-LD structured data
├── Policies/
│   └── PostPolicy.php     # Authorization for viewing/editing posts
├── PostProcessingStatus.php    # Enum: post processing states
└── TagCategory.php             # Enum: tag categories with prefixes
bootstrap/            # Laravel bootstrap
config/               # Configuration files
database/
├── factories/    # Model factories (Post, User, Tag, Comment)
├── migrations/   # Schema migrations
└── seeders/      # Database seeders
public/               # Public assets and entry point
resources/
├── css/
│   ├── app.css       # Entry point (imports others)
│   ├── base.css      # Reset, typography, variables
│   ├── layout.css    # Page shell, sidebar, grid
│   ├── components.css# Buttons, forms, cards, comments
│   └── pages.css     # Page-specific styles
├── js/
│   ├── app.js        # JS entry point
│   ├── bootstrap.js  # Axios setup (Laravel scaffold — not imported yet)
│   ├── search.js     # Tag autocomplete logic
│   └── account.js    # Account page interactions
├── views/
│   ├── auth/         # Login & register views
│   │   ├── login.blade.php
│   │   └── register.blade.php
│   ├── post/         # Post listing, creation, detail views
│   │   ├── create.blade.php
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── account/      # Account management views
│   │   └── show.blade.php
│   ├── users/        # Public user profiles
│   │   └── show.blade.php
│   └── components/   # Reusable Blade components
│       ├── layout.blade.php
│       ├── header.blade.php
│       ├── footer.blade.php
│       ├── search.blade.php
│       ├── theme.blade.php
│       ├── post-card.blade.php
│       └── post/
│           ├── tags.blade.php
│           ├── media.blade.php
│           └── details.blade.php
routes/
├── web.php           # All web routes
└── console.php       # Console route definitions
storage/              # Uploads, logs, cached views, ffmpeg binary
tests/                # Pest tests
```

## Key Architectural Patterns

- **Service classes** (`app/Services/`) encapsulate business logic — prefer adding logic there over controllers.
- **Queued jobs** (`app/Jobs/`) handle heavy processing (media transcoding, avatar cropping) asynchronously. All jobs should implement a `failed()` method for robust error handling and observability.
- **Hash-based file storage:** Uploaded files are stored by content hash (`xx/xx/hash.ext`) to deduplicate.
- **Tag system:** Tags have 7 categories defined in the `TagCategory` enum:
  - `artist` (prefix `a:`), `copyright` (`c:`), `origin` (`o:`), `template` (`t:`), `general` (`g:`), `meta` (`m:`), `language` (`l:`)
  - Both short (`a:`) and long (`artist:`) prefix forms are supported
  - Tag names are normalized: lowercase, spaces→underscores, allowed chars `[a-z0-9_():-]`
  - Always use `TagService` for parsing/normalizing — never manually
- **Invite-only registration:** Users need a valid 32-char invite code to register. Invites are created by existing users.
- **Posts are unlisted by default** after upload and go through a processing pipeline.
- **Notifications:** Laravel database notifications are set up (`AvatarProcessed`). Use `$user->notify(new SomeNotification())` — do not build a custom notifications table.
- **Tag aliases:** Tags may alias another tag within the same category. Tags have a composite unique key `(name, category)` — the same name can exist across different categories (e.g. `g:instagram` and `o:instagram` are distinct).

## Models & Relationships

- `Post` -> belongsTo `User` (author), hasMany `Vote`, hasMany `Comment`, belongsToMany `Tag`
- `User` -> hasMany `Post`, hasMany `Vote`, hasMany `Invite`. Uses implicit route binding to `username`.
- `Tag` -> belongsToMany `Post`, may alias another `Tag`
- `Vote` -> belongsTo `Post`, belongsTo `User` (value is +1 or -1)
- `Comment` -> belongsTo `Post`, belongsTo `User`; fields: `post_id`, `user_id`, `content`
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
- File uploads max size is 100MB. Supported types: JPEG, PNG, GIF, WebP, MP4, WebM. File uploads can also be made via URL source.
- Tag parsing supports category prefixes (`category:name`) — always use `TagService` for parsing/normalizing tags, never do it manually.
- New posts have `is_listed = false` and `processing_status = Processing` by default — they become visible after processing completes.
- Duplicate detection is hash-based — two identical files will be rejected.
- The autocomplete endpoint `GET /tags/autocomplete?q=` is public and returns JSON. Frontend autocomplete lives in `resources/js/search.js`.

## Testing

- Tests live in `tests/`.
- Tests use **Pest** with a memory database for speed — `RefreshDatabase` wipe between runs.
- Run with: `php artisan test`
- In the testing environment `PostFactory` automatically uses fake local data (no `picsum.photos` download) — no special state needed. The `fake()` state does **not** exist; `fakeDefinition()` is triggered automatically via `app()->environment('testing')`.
- When adding new features, include matching test coverage.
- Use PHPStan for static analysis before committing.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `livewire-development` — Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, wire:sort, or islands, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, drag-and-drop, loading states, migrating from Livewire 3 to 4, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire.
- `pest-testing` — Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
