# My Favorite News

A small, personal news aggregator. It fetches RSS feeds from several news
sources every 4 hours, and flags a story as **important** when the same
story shows up in 2 or more different outlets. You get one simple
"briefing" page instead of checking many news sites.

## Features

- Fetches RSS/Atom feeds every 4 hours (00:00, 04:00, 08:00, 12:00, 16:00,
  20:00), plus a manual "Fetch now" button in the panel.
- Detects duplicate stories across **outlets** (not just RSS feeds — see
  below) using plain text similarity on the title — no AI, no API cost.
- Marks a story cluster "important" once 2+ different outlets report it;
  hovering the ⭐ badge shows which outlets. Important is a **toggle**, top
  right of the tabs — it combines with whichever tab is active (All, a
  category, or Favorites), it's not its own separate tab. A tag/keyword's
  chips still show on cards while Important is on, even without switching to
  Favorites, so you can build up your included/excluded list as you browse.
- **Unread** toggle (●), left of Important — same combining behavior, so
  "Important + Unread" (or any other combo) works together.
- Pulls full article body when a feed actually provides one (`content:encoded`,
  common on WordPress-based feeds); a "Read here" button opens it in a
  scrollable modal, no images. Many outlets (NPR, NBC, CBS, ABC) only publish
  a short teaser in their feed — for those, the modal says so and links out.
- Translate to Persian: a 🌐 button on every card translates the title +
  summary in place (RTL) — click again to revert. A separate "Translate to
  Persian" button in the "Read here" modal translates the full body. Both go
  through DeepSeek and cache their result per article so re-translating is
  free. No daily cap — this is a single-user app, so DeepSeek usage is left
  uncapped; keep an eye on your own DeepSeek billing if that matters to you.
- Tags parsed straight from each feed's `<category>` elements, shown as chips
  under each article. Click "+"/"−" on a chip to include/exclude that tag.
- A dedicated **Tags** page lists included/excluded tags (with a one-click
  remove) and lets you type in a brand-new keyword — a person's or country's
  name, say — to include. A tag/keyword matches an article either because
  the feed tagged it that way, or because the word literally appears in the
  title/summary, so a manually-typed name works even if no feed tagged it.
- **Favorites** = a live filter, not a saved list: articles matching at least
  one *included* tag/keyword and none of the *excluded* ones. Change them on
  the Tags page and Favorites updates immediately — nothing to "reprocess".
- One simple login (single admin account, no public registration).
- Automatic cleanup: articles older than 10 days (by publish date, not fetch
  date) are deleted every fetch cycle. There's no manual delete — if you
  don't want to read something, just don't click it and move on.
- Settings page: hide whole categories from your default briefing.
- Feed manager: add / edit / pause / delete RSS feeds from the panel.

## How duplicate detection works

`app/Services/DuplicateDetectorService.php` normalizes each title (lowercase,
strips punctuation and common stop-words), then compares it to other
articles from a **different outlet** (by feed *name*, not feed row — so the
same outlet's politics feed and economy feed carrying the same story doesn't
falsely count as 2 sources) published within 48 hours, using a word-overlap
ratio (Jaccard similarity, threshold 0.55). Matching articles share an
`article_clusters` row; once that cluster has 2+ distinct outlets, it's
flagged `is_important`.

**The briefing shows one card per cluster, not one per article.** Detection
happening correctly (the ⭐ badge, tooltip, sources count) doesn't by itself
stop the same story appearing 3 times just because 3 outlets ran it — that's
`ArticleController::oneArticlePerClusterSql()`: a `ROW_NUMBER() OVER (PARTITION
BY ...)` SQL filter that keeps exactly one representative row per cluster
(preferring one with full content, then the most recent), applied before
category/tag/favorites filtering. A story that got tagged into two different
categories by two different feeds (e.g. one outlet's IT feed and another's
Economy feed both ran it) will show under whichever category the chosen
representative belongs to — not both.

This is intentionally simple and free to run. It will miss duplicates that
are worded very differently. If that becomes a problem, `RssFetcherService`
and `DuplicateDetectorService` are the two files to extend — e.g. swapping in
an LLM-based similarity check for the ones that don't match by words alone.

## Translation (DeepSeek)

Set `DEEPSEEK_API_KEY` in `.env`. `app/Http/Controllers/TranslationController.php`
caches each result permanently once made (`articles.title_fa`/`description_fa`
for the card, `translation_fa` for the full body), so revisiting an
already-translated article is free and doesn't call the API again. If the
key is missing, the button just returns an error instead of failing the
whole page. There's no usage cap — add one back in this controller if that's
ever wanted.

## Tags and Favorites

Tags come from whatever each feed's `<category>` elements contain — quality
varies by source (some are clean topics like "politics", others are internal
taxonomy slugs). The **Tags** page (`app/Http/Controllers/TagController.php`)
holds two lists, `included_tags` / `excluded_tags`, stored as JSON in the
`settings` table. You can add a brand-new entry there that was never a real
tag from any feed — a person's or country's name, for example.

A term (from either list) matches an article if **either**: the article has
a real tag with that exact name, **or** the term appears as text in the
title or description (`ArticleController::orMatchesTerm()` /
`whereDoesntMatchTerm()`). That's what makes a manually-typed keyword work —
it's matched by substring search, not just the feed's own tag metadata.

The Favorites tab query is: matches an included term, and matches no
excluded term. If the included list is empty, Favorites shows nothing
(there's nothing to ask for yet) — the page tells you this and links to Tags.

Tag chips on a card show tags pooled from **every article in that story's
cluster**, not just the one shown (`Article::getAllTagsAttribute()`). Only
3 of the 7 seeded sources (Fox News, Fox Business, Washington Examiner)
supply RSS `<category>` data — without pooling, a cluster whose chosen
representative happened to be NBC/CBS/ABC/NPR/The Hill would show no tags
at all, even though a Fox member of the same cluster had some.

Mark-read, the 🌐 translate button, and the tag chip +/− buttons are all
AJAX (`fetch`, no page reload) — clicking any of them while scrolled deep
into a long list doesn't reset your scroll position.

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
DEEPSEEK_API_KEY=            # optional — only needed for the translate button
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
php artisan news:fetch     # fetch all feeds, dedup, then runs news:cleanup
php artisan news:cleanup   # delete articles older than 10 days on their own
```

`news:fetch` is scheduled every 4 hours in `routes/console.php`, plus a
minute-granularity check for the "Fetch now" button (it sets a cache flag;
the next scheduler tick, within ~1 minute, picks it up and runs the fetch —
no separate background worker needed). In production, a single cron entry
drives all of this:

```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Changing the fetch schedule, retention, or feeds

- Fetch schedule: edit the `Schedule::command('news:fetch')->cron(...)` line
  in `routes/console.php`.
- Retention window: `RETENTION_DAYS` constant in
  `app/Console/Commands/CleanupOldArticles.php`.
- Feeds: use the Feeds panel in the app, or edit
  `database/seeders/FeedSeeder.php` and re-run `php artisan db:seed --class=FeedSeeder`.

## Stack

Laravel 13, SQLite (no separate DB server needed), Blade + Tailwind (via CDN,
no JS build step). Deliberately light for small/shared hosting.
