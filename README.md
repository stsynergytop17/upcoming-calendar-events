# Upcoming Calendar Events

A WordPress plugin that displays upcoming events from a Google Calendar on the front-end of any WordPress site.

---

## Features

- **Shortcode** `[upcoming_events]` renders the next N upcoming events on any page or post
- **Settings page** under *Settings → Calendar Events* for API key, cache duration, and default event count
- **WP Transient caching** to avoid hitting Google's rate limits on every page load
- **AJAX rendering** — events load asynchronously so page caches (Varnish, WP Super Cache, etc.) don't interfere
- **Manual cache flush** button in the admin settings page
- Clean, semantic HTML output with minimal default styling
- Graceful error handling — shows friendly messages on network failures, bad keys, and empty results
- No events? No breakage.

---

## Requirements

- **WordPress** 6.0 or later
- **PHP** 8.0 or later
- A **Google Cloud project** with the Calendar API enabled (free tier is sufficient for most sites)

---

## Installation

### 1. Upload the plugin

**Option A — Composer (recommended)**

```bash
cd wp-content/plugins/
git clone https://github.com/your-username/upcoming-calendar-events.git
cd upcoming-calendar-events
composer install --no-dev --optimize-autoloader
```

**Option B — Manual (no Composer)**

Upload the plugin folder to `wp-content/plugins/`. The plugin includes a fallback PSR-4 autoloader that runs automatically when `vendor/autoload.php` is absent.

### 2. Activate the plugin

Go to *Plugins → Installed Plugins* and activate **Upcoming Calendar Events**.

---

## Obtaining a Google API Key

1. Go to [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project (or select an existing one).
3. Navigate to **APIs & Services → Library**.
4. Search for **Google Calendar API** and click **Enable**.
5. Go to **APIs & Services → Credentials**.
6. Click **Create Credentials → API key**.
7. (Recommended) Click **Restrict Key**:
   - Under *API restrictions*, select **Google Calendar API**.
   - Under *Application restrictions*, add your site's domain.
8. Copy the API key.

---

## Finding Your Google Calendar ID

1. Open [Google Calendar](https://calendar.google.com/).
2. In the left sidebar, hover over the calendar you want to share, then click the three-dot menu → **Settings and sharing**.
3. Scroll down to **Integrate calendar**.
4. Copy the **Calendar ID** (looks like `name@group.calendar.google.com` or `abc123xyz@google.com`).
5. Ensure the calendar's **Access permissions** are set to *Make available to public* (or at least accessible by the API key's project) — otherwise the API will return a 403.

---

## Plugin Configuration

Go to **Settings → Calendar Events** in your WordPress admin:

| Field | Default | Description |
|---|---|---|
| Google API Key | *(empty)* | Your Google Cloud API key |
| Cache Duration | 60 minutes | How long to cache API responses (1–1440 minutes) |
| Default Number of Events | 5 | Fallback `count` when shortcode omits it |

Click **Save and Validate** — the plugin makes a lightweight test request to confirm the API key is working.

---

## Shortcode Reference

```
[upcoming_events
    count="10"
    days_ahead="30"
    show_description="true"
    google_calendar_id="your-calendar@group.calendar.google.com"]
```

| Attribute | Required | Default | Description |
|---|---|---|---|
| `google_calendar_id` | **Yes** | — | The Google Calendar ID |
| `days_ahead` | **Yes** | — | How many days ahead to search for events |
| `count` | No | Settings value | Maximum events to display |
| `show_description` | No | `false` | Whether to show event descriptions |

### Examples

Show the next 5 events in the next 30 days:
```
[upcoming_events days_ahead="30" google_calendar_id="abc@group.calendar.google.com"]
```

Show 10 events with descriptions:
```
[upcoming_events count="10" days_ahead="60" show_description="true" google_calendar_id="abc@group.calendar.google.com"]
```

---

## Theming & Styling

All front-end classes follow a `uce-` prefix and BEM-like structure:

```
.uce-events-wrapper          — outer container
.uce-events-list             — <ol> list
.uce-event                   — individual <li>
.uce-event--all-day          — modifier for all-day events
.uce-event__title            — event heading
.uce-event__meta             — date/time wrapper
.uce-event__start            — <time> for start
.uce-event__end              — <time> for end
.uce-event__location         — location string
.uce-event__description      — description block
.uce-loading                 — AJAX loading state
.uce-error                   — error message
.uce-no-events               — empty-state message
```

Override any of these in your theme's `style.css` to customise the appearance.

---

## Architecture Decisions

### Object-Oriented + PSR-4

The codebase is split into four namespaced subsystems under `src/`:

```
src/
├── Plugin.php               — bootstraps everything, registers hooks
├── Admin/
│   └── SettingsPage.php     — settings page UI, form handling, validation
├── API/
│   ├── GoogleCalendarClient.php  — HTTP wrapper around the Google API
│   └── EventFetcher.php          — orchestrates API + cache
├── Cache/
│   └── TransientCache.php   — WP Transient abstraction with a flush registry
└── Frontend/
    ├── Shortcode.php        — [upcoming_events] shortcode registration
    └── AjaxHandler.php      — wp_ajax_* handler; renders event HTML
```

Each class has a single responsibility and depends only on well-defined interfaces, making them independently testable.

### AJAX rendering (not inline)

The shortcode outputs a lightweight `<div>` placeholder; JavaScript loads the actual event list via AJAX. This means:

- Full-page caches (Varnish, WP Super Cache, LiteSpeed) still work correctly.
- Multiple shortcode instances on the same page each fire their own request independently.
- The nonce is injected via `wp_localize_script` so it's always fresh.

### WP Transient caching

Cache keys are deterministic `md5` hashes of `(calendar_id + count + days_ahead)`, so different shortcode configurations are cached independently. A **transient registry** option (`uce_transient_registry`) tracks all active keys so a single `flush_all()` call can purge everything without an expensive `LIKE` query on the options table.

### Input/output discipline

- Every value from `$_POST`, `$_GET`, and the API is sanitised on the way in (`sanitize_text_field`, `absint`, `wp_kses_post`).
- Every value emitted to HTML is escaped on the way out (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
- All admin actions are protected by nonces and `current_user_can('manage_options')` checks.

---

## Bonus Items (Given More Time)

- **Gutenberg block** — register a block that wraps the same AJAX logic with a block editor panel for setting attributes.
- **Multiple calendars merged** — pass a comma-separated list of IDs; `EventFetcher` makes parallel requests, merges, and sorts by start time.
- **Event grouping by day** — add a `group_by_day` shortcode attribute; the `AjaxHandler` renderer groups events into `<section>` elements with `<h4>` date headings.
- **Frontend "load more"** — store a `pageToken` from the Google API response; a "Load more" button fires another AJAX request with the token appended.
- **Unit tests** — PHPUnit + WP_Mock for the `GoogleCalendarClient`, `TransientCache`, and `AjaxHandler` classes.

---

## AI Disclosure

Approximately 70% of this project was developed with AI assistance (ChatGPT and Claude) acting as pair-programming and code-review tools. AI was used to help generate initial class structures, boilerplate code, documentation, and implementation ideas for the Google Calendar integration, AJAX rendering, caching, and plugin architecture.

I manually reviewed, tested, modified, and integrated all generated code. During development, I was responsible for debugging and resolving issues related to the Google Calendar API integration, including investigating why events were not displaying, validating API requests, testing Calendar IDs and API keys, inspecting API responses, and correcting the event query logic. I also verified shortcode behavior, caching functionality, AJAX responses, and front-end rendering within WordPress.

All final implementation decisions, testing, debugging, and code integration were performed by me.
