# mm

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![License](https://img.shields.io/badge/license-AGPL%20v3-blue)

A meme imageboard and archive. Browse, search, and upload images and videos with a categorized tag system, voting, favorites, and comments. Invite-only registration.

## Features

- Image and video uploads (JPEG, PNG, GIF, WebP, MP4, WebM) via file or URL
- Hash-based deduplication — identical files are rejected automatically
- Categorized tag system with autocomplete and alias support
- Tag-based search with metatag filters (`score:`, `views:`, `uploader:`, `date:`, `order:`)
- Voting, favorites, and comments
- Invite-only registration
- Public user profiles
- Discord webhook notifications for new posts
- Sitemap generation

## Tech Stack

- **Backend:** PHP 8.5, Laravel 13
- **Frontend:** Blade templates, vanilla JavaScript and CSS
- **Build tool:** Vite
- **Database:** MySQL / MariaDB (SQLite supported for local dev)
- **Media processing:** FFmpeg (self-managed binary)
- **Queue:** Laravel database queue

## Requirements

- PHP 8.2+
- Composer
- Node.js and npm
- A database (MySQL/MariaDB recommended for production, SQLite for local dev)

## Local Setup

```bash
git clone https://github.com/Anfeket/mm
cd mm
composer run setup
```

The `setup` script installs dependencies, copies `.env.example` to `.env`, generates an app key, runs migrations, and builds frontend assets.

Then start the development environment:

```bash
composer run dev
```

This runs the Laravel server, queue listener, and Vite dev server concurrently.

### FFmpeg

Video processing and thumbnails require FFmpeg. Install the bundled static binary with:

```bash
php artisan ffmpeg:install
```

This downloads a static Linux x86-64 build to `storage/bin/`. Override the download URL via `FFMPEG_URL` in `.env` if you need a different architecture.

### First User

Registration is invite-only. Seed the database to create an admin account and an initial invite code:

```bash
php artisan db:seed
```

Default credentials: `Admin` / `password`. The invite code is `INVITE123`. Change both immediately.

## Configuration

Most `.env` values are standard Laravel. The ones specific to this project:

| Variable | Default | Description |
|---|---|---|
| `DISCORD_WEBHOOK_URL` | *(unset)* | Optional. Posts a webhook embed when a new post finishes processing. |
| `MEDIA_HASH_ALGO` | `md5` | Hashing algorithm used for file deduplication. Any algorithm supported by PHP's `hash_algos()` is valid. |
| `FFMPEG_URL` | BtbN Linux x86-64 GPL build | Download URL used by `php artisan ffmpeg:install`. Override for a different architecture. |
| `APP_GOOGLE_SITE_VERIFICATION` | *(unset)* | Optional. Outputs a `<meta>` tag for Google Search Console verification. |

For production, set `DB_CONNECTION=mysql` (or `pgsql`) and configure the corresponding `DB_*` variables. The default SQLite setup is only suitable for local development.

## Deployment

```bash
npm run build
php artisan optimize
php artisan migrate --force
```

Run a persistent queue worker to handle media processing:

```bash
php artisan queue:work --tries=3
```

## License

[GNU General Public License v3.0](LICENSE)
