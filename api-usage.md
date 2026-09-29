# API reference

A small JSON API over the same briefing you see on the web page. Another app
can use it to read news, mark what it read, and ask for a new fetch.

- Base URL: `https://news.peppasoft.com/api/v1` (locally: `http://127.0.0.1:8000/api/v1`)
- All responses are JSON.
- Rate limit: **60 requests per minute** per API key. Over the limit you get
  `429 Too Many Requests` with a `Retry-After` header.

## Authentication

Get your key from the **API** menu in the web panel. There you can also
**Rotate** it: this makes a new key, and the old key stops working at once.

Send the key in **one** of these headers:

```
X-API-Key: nfp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Authorization: Bearer nfp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

The key is **not** accepted in the URL (`?api_key=...`), because URLs are
saved in server and proxy logs.

A missing or wrong key returns:

```json
HTTP 401
{ "message": "Missing or invalid API key." }
```

## Endpoints

| Method | Path | What it does |
|---|---|---|
| `GET` or `POST` | `/articles` | List news, with filters and paging. Can mark the returned page as read. |
| `POST` | `/fetch-now` | Ask the server to fetch all RSS feeds now. |
| `GET` | `/status` | Last fetch time, whether a fetch is waiting, unread count. |

---

## `GET /articles` (or `POST /articles`)

Returns the same cards as the web Briefing: **one card per story** (when
several outlets report the same story, you get one item, with all outlets in
`sources`). Newest first (by `published_at`).

Arguments can be sent as query-string parameters, or (for `POST`) as a JSON
body. You can mix them. Tip: use `POST` when you send `mark_read=1`, because
that call changes data.

### Arguments

All are optional.

| Name | Type | Default | Meaning |
|---|---|---|---|
| `category` | `politics` \| `economy` \| `it` | none | Only this category. Without it you get "All" (same as the web: categories hidden on the Settings page are left out of "All"). |
| `favorites` | bool | `false` | Only Favorites: matches an included tag/keyword and no excluded one (set on the Tags page). If no tag is included, the result is empty. |
| `important` | bool | `false` | Only stories reported by 2+ different outlets. |
| `unread` | bool | `false` | Only stories not marked as read. |
| `from` | date or date-time | none | Published at or after this time. |
| `to` | date or date-time | none | Published at or before this time. Must not be before `from`. |
| `page` | int ≥ 1 | `1` | Page number. |
| `per_page` | int 1–100 | `10` | Items per page. |
| `mark_read` | bool | `false` | Mark the items **of the returned page only** as read (see below). |
| `include_content` | bool | `false` | Add the full article body (`content`) to each item. Only some feeds provide it; see `has_full_content`. |

**Bool values:** `1`, `true`, `on`, `yes` mean true. Anything else (or leaving
it out) means false.

**Filters combine**, like the web tabs and toggles. Examples:

| Web view | Arguments |
|---|---|
| All + Important + Unread | `important=1&unread=1` |
| IT + Unread | `category=it&unread=1` |
| Favorites + Important | `favorites=1&important=1` |

**Dates:**

- A date only (`2026-09-25`) means the **start** of that day for `from` and
  the **end** of that day for `to`. So `from=2026-09-25&to=2026-09-25` is
  that whole day.
- A date-time is used as given, e.g. `2026-09-25T14:00:00Z` or
  `2026-09-25T17:30:00+03:30`.
- Times without an offset are **UTC**. All times in responses are UTC.
- Stories with no publish date are left out when you use `from` or `to`.

### How `mark_read` works

`mark_read=1` marks **only the items in this response** as read — not all
items that match the filters. Items on other pages are not changed.

Use it together with `unread=1` and **always ask for page 1**. Each call then
gives you the next batch:

```
23 unread important stories.

Call 1: important=1&unread=1&mark_read=1  -> items 1–10,  marks them read
Call 2: same                              -> items 11–20, marks them read
Call 3: same                              -> items 21–23, marks them read
Call 4: same                              -> empty list (data: [])
```

Things to know:

- Do **not** send `page=2`, `page=3`, ... in this mode. After call 1 the list
  is shorter, so page 2 would skip items.
- Without `unread=1`, `mark_read=1` still marks the page, but the next call
  returns the same items again (they are still in the list).
- In the response, `is_read` shows the state **before** this call. So with
  `unread=1` every item shows `false`. `meta.marked_read` tells you how many
  items this call marked.
- Read state is **shared** with the web page. What the API marks as read also
  looks read on the web, and the other way around.
- Only the shown card of a story is marked (the same as the web "Mark read").
  If a new outlet later reports the same story, that story can show up as
  unread again.

### Response

```json
HTTP 200
{
  "data": [
    {
      "id": 1234,
      "title": "Senate passes budget bill",
      "description": "The Senate voted 52-48 on ...",
      "url": "https://www.example.com/politics/budget-bill",
      "category": "politics",
      "source": "NBC News",
      "published_at": "2026-09-29T09:15:00+00:00",
      "is_read": false,
      "is_important": true,
      "sources_count": 3,
      "sources": ["NBC News", "Fox News", "The Hill"],
      "tags": ["congress", "budget"],
      "title_fa": null,
      "description_fa": null,
      "has_full_content": false
    }
  ],
  "links": {
    "first": "https://news.peppasoft.com/api/v1/articles?important=1&page=1",
    "last": "https://news.peppasoft.com/api/v1/articles?important=1&page=3",
    "prev": null,
    "next": "https://news.peppasoft.com/api/v1/articles?important=1&page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "path": "https://news.peppasoft.com/api/v1/articles",
    "per_page": 10,
    "to": 10,
    "total": 23,
    "links": [ ... ],
    "marked_read": 0
  }
}
```

Item fields:

| Field | Type | Notes |
|---|---|---|
| `id` | int | Article id. |
| `title`, `description` | string, string\|null | As given by the feed. |
| `url` | string | Link to the original article. |
| `category` | string | `politics`, `economy` or `it`. |
| `source` | string | Outlet of the shown card. |
| `published_at` | string\|null | ISO 8601, UTC. |
| `is_read` | bool | State before this call (see `mark_read`). |
| `is_important` | bool | Reported by 2+ different outlets. |
| `sources_count`, `sources` | int, string[] | All outlets that reported this story. |
| `tags` | string[] | Tags from all outlets of this story. |
| `title_fa`, `description_fa` | string\|null | Persian translation, only if someone already clicked 🌐 on the web. The API does not translate. |
| `has_full_content` | bool | The feed gave a full article body. |
| `content` | string\|null | Only with `include_content=1`. |

`meta.total` and `meta.last_page` are counted **before** `mark_read` is applied.

### Errors

Wrong arguments return `422` with details:

```json
HTTP 422
{
  "message": "The selected category is invalid.",
  "errors": { "category": ["The selected category is invalid."] }
}
```

For example: an unknown `category`, `per_page` over 100, a bad date, or `to`
before `from`.

---

## `POST /fetch-now`

Asks for a fetch of all active feeds, the same as the web "⟳ Fetch now"
button. The fetch does **not** run during this call: the server's scheduler
starts it within about 1 minute. It usually finishes a minute or two later.

```json
HTTP 202
{
  "queued": true,
  "message": "Fetch queued. It starts within about 1 minute; poll /api/v1/status to see when it is done.",
  "last_fetched_at": "2026-09-29T08:00:04+00:00"
}
```

To know when new news is in: call `/status` about once a minute. The fetch
has run when `fetch_pending` is `false` **and** `last_fetched_at` is newer
than the value you got from `/fetch-now`. `last_fetched_at` changes as soon
as the first feed is done, so wait about one more minute before you read, to
be sure all feeds are in.

Feeds are also fetched on their own every 4 hours (00, 04, 08, 12, 16, 20 UTC).

## `GET /status`

```json
HTTP 200
{
  "last_fetched_at": "2026-09-29T08:00:04+00:00",
  "fetch_pending": false,
  "unread_count": 57,
  "categories": ["politics", "economy", "it"]
}
```

- `fetch_pending`: `true` from a `/fetch-now` call until the scheduler starts
  that fetch.
- `unread_count`: all unread articles in the database. This counts every
  outlet's copy of a story, so it can be bigger than the number of unread
  cards.

---

## Examples

```bash
KEY=nfp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
BASE=https://news.peppasoft.com/api/v1

# All + Important + Unread (10 per page)
curl -H "X-API-Key: $KEY" "$BASE/articles?important=1&unread=1"

# IT + Unread, 25 per page, page 2
curl -H "X-API-Key: $KEY" "$BASE/articles?category=it&unread=1&per_page=25&page=2"

# Last 3 days, politics only
curl -H "X-API-Key: $KEY" "$BASE/articles?category=politics&from=2026-09-27"

# Read the next 10 unread important stories and mark them read (JSON body)
curl -X POST -H "X-API-Key: $KEY" -H "Content-Type: application/json" \
     -d '{"important": true, "unread": true, "mark_read": true}' \
     "$BASE/articles"

# Ask for a fetch, then check status
curl -X POST -H "X-API-Key: $KEY" "$BASE/fetch-now"
curl -H "X-API-Key: $KEY" "$BASE/status"
```

Read everything unread, 10 at a time (Python):

```python
import requests

BASE = "https://news.peppasoft.com/api/v1"
HEADERS = {"X-API-Key": "nfp_..."}

while True:
    r = requests.post(f"{BASE}/articles", headers=HEADERS,
                      json={"unread": True, "mark_read": True})
    r.raise_for_status()
    items = r.json()["data"]
    if not items:
        break
    for item in items:
        print(item["published_at"], item["source"], item["title"])
```

## Code map

| Part | File |
|---|---|
| Routes | `routes/api.php` |
| Key check | `app/Http/Middleware/AuthenticateApiKey.php` |
| Endpoints | `app/Http/Controllers/Api/ArticleController.php` |
| Item JSON | `app/Http/Resources/ArticleResource.php` |
| Filters (shared with the web page) | `app/Services/ArticleFilter.php` |
| Key storage / rotate | `app/Models/User.php` (`rotateApiKey()`, `findByApiKey()`) |
| API menu page | `app/Http/Controllers/ApiKeyController.php`, `resources/views/api-key/show.blade.php` |
| Rate limit | `app/Providers/AppServiceProvider.php` |
| Tests | `tests/Feature/ApiArticlesTest.php` |

The key is stored **encrypted** with `APP_KEY` (so the API page can show it
again) plus a SHA-256 hash that is used to find the user on each request.
If `APP_KEY` changes, the stored key can't be read any more: rotate it.
