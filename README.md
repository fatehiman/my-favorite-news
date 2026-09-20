# My Favorite News

A small, personal news aggregator. It fetches RSS feeds from several news
sources every day, and flags a story as **important** when the same story
shows up in 2 or more different sources. You get one simple "briefing" page
instead of checking many news sites.

## Features

- Fetches RSS/Atom feeds on a daily schedule (default 9:00 AM).
- Detects duplicate stories across sources using plain text similarity
  (word overlap on the title) — no AI, no API cost.
- Marks a story cluster "important" once 2+ different sources report it.
- One simple login (single admin account, no public registration).
- Briefing page: filter by category (politics / economy / IT), show only
  important stories, show only favorites, mark read, remove.
- Settings page: hide whole categories from your default briefing.
- Feed manager: add / edit / pause / delete RSS feeds from the panel.

## How duplicate detection works

`app/Services/DuplicateDetectorService.php` normalizes each title (lowercase,
strips punctuation and common stop-words), then compares it to other
articles from **different feeds** published within 48 hours using a
word-overlap ratio (Jaccard similarity, threshold 0.55). Matching articles
share an `article_clusters` row; once that cluster has articles from 2+
distinct feeds, it's flagged `is_important`.

This is intentionally simple and free to run. It will miss duplicates that
are worded very differently. If that becomes a problem, `RssFetcherService`
and `DuplicateDetectorService` are the two files to extend — e.g. swapping in
an LLM-based similarity check for the ones that don't match by words alone.

## Seeded RSS feeds

21 feeds from 7 English-language, non-British US news sources, 3 categories
each (politics / economy / IT): **NPR, NBC News, ABC News, Fox News (+ Fox
Business for economy), CBS News, The Hill, Washington Examiner**.

These were hand-verified (fetched and checked for valid, on-topic, current
content) before being added to `database/seeders/FeedSeeder.php`. A few other
major outlets (CNN, CNBC, Politico, NYT, Reuters, etc.) were deliberately left
out — either because they're British/UK-based, or because a genuinely
separate, working feed for all 3 categories couldn't be verified. Add more any
time from the Feeds panel.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set:

```
ADMIN_USERNAME=choose-a-username
ADMIN_PASSWORD=choose-a-strong-password
```

Then:

```bash
php artisan migrate --seed   # creates the admin user + seeds the 21 feeds
php artisan serve            # local dev server
```

Log in with the `ADMIN_USERNAME` / `ADMIN_PASSWORD` from `.env` (login is by
username, not email).

## Fetching news

```bash
php artisan news:fetch
```

This is scheduled daily at 9:00 AM in `routes/console.php`. In production, a
single cron entry drives Laravel's scheduler:

```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Changing the fetch time or adding feeds

- Fetch time: edit `Schedule::command('news:fetch')->dailyAt('09:00')` in
  `routes/console.php`.
- Feeds: use the Feeds panel in the app, or edit
  `database/seeders/FeedSeeder.php` and re-run `php artisan db:seed --class=FeedSeeder`.

## Stack

Laravel 13, SQLite (no separate DB server needed), Blade + Tailwind (via CDN,
no JS build step). Deliberately light for small/shared hosting.
