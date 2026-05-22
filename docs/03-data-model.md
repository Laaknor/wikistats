# Data Model

## Entity Relationship Overview

```
WikidataTracking 1 ──────┬──────── N Category
                         │
Site 1 ──────────────────┼──────── N Category
                         │
                         └──────── N CategoryCount (by date)

Site 1 ─────── N Chart ──────┬──── N Category (chart_category pivot: sort_order, label, color)
                              │
                              └──── Chart has many Categories (many-to-many)

Site 1 ─────────────────────────── N Siteinfo (by info type + date)

ArchiveItem 1 ──────────────────── N ArchiveFile
```

## Core Entities

### Site

Represents a wiki (e.g. en.wikipedia.org, fr.wiktionary.org).

| Attribute | Type | Description |
|-----------|------|-------------|
| id | PK | |
| language | string | e.g. `en`, `fr` |
| family | string | e.g. `wikipedia`, `wiktionary` |
| dbname | string | e.g. `enwiki`, `frwiktionary` |
| hostname | string | e.g. `en.wikipedia.org` |
| url | string | Base URL with trailing slash |
| enabled | bool | Default false |
| last_siteinfo | datetime, nullable | Last time siteinfo was fetched |
| timestamps | | created_at, updated_at |

**Relations:** `categories`, `charts`, (Siteinfo via site_id).

**Notes:** `Site::parseUrl($url)` parses a sitelink URL and returns `firstOrCreate` Site (e.g. en.wikipedia.org → language=en, family=wikipedia, dbname=enwiki).

---

### Category

A category on a wiki that is tracked for counts. Created from Wikidata sitelinks or otherwise linked to a WikidataTracking.

| Attribute | Type | Description |
|-----------|------|-------------|
| id | PK | |
| site_id | FK → Site | |
| wikidata_tracking_id | FK → WikidataTracking | |
| name | text | Wiki category page name (e.g. with underscores) |
| type | string | `categorycount` or `subcategorycount` (set by GetCategoryCountsJob) |
| last_sync | datetime | Last time counts were synced |
| is_active | bool | When false, the category was deleted (or missing) on the wiki; GetCategoryCountsJob skips it |
| mw_category_id | int, nullable | MediaWiki page id |
| display_name | string, nullable | Human-readable title (e.g. with spaces) |
| timestamps | | created_at, updated_at |

**Relations:** `site`, `wikidata_tracking`, `category_counts`, `charts` (via chart_category).

**Count semantics:**
- **categorycount:** stored count is the category’s direct page count from `categoryinfo.pages`.
- **subcategorycount:** stored count is the sum of `categoryinfo.pages` over all direct subcategories (used when subcats > pages to reflect “content in tree” rather than direct pages).

---

### CategoryCount

One row per category per day: the count value for that day.

| Attribute | Type | Description |
|-----------|------|-------------|
| id | PK | |
| category_id | FK → Category | |
| date | date | Day this count applies to |
| count | int | Page count (or subcategory sum) |
| timestamps | | created_at, updated_at |

**Relations:** `category`.

**Uniqueness:** One row per (category_id, date); jobs use `updateOrCreate` on (category_id, date).

---

### Siteinfo

Site-level statistics per day (from live API or historical import).

| Attribute | Type | Description |
|-----------|------|-------------|
| site_id | FK → Site | (part of logical key) |
| info | string | e.g. `pages`, `users`, `edits`, `articles`, `activeusers`, `admins`, `images` |
| date | date | Day this stat applies to |
| count | int | Value |
| (no id / timestamps) | | Table has composite key (site_id, info, date) |

**Relations:** `site`.

---

### WikidataTracking

Administrative record: a Wikidata item ID to track. Sitelinks from that item become Categories on the corresponding Sites.

| Attribute | Type | Description |
|-----------|------|-------------|
| id | PK | |
| item | string | Wikidata item ID (e.g. Q12345) |
| type | string, nullable | e.g. `categorycount`, `subcategorycount`; applied to created categories |
| group | string, nullable | `maintenance`, `content`, or null; used to group categories into tabs on the site page |
| last_sync | datetime | Last time sitelinks were fetched |
| name | string, nullable | Optional label |
| description | text, nullable | Optional description |
| timestamps | | created_at, updated_at |

**Relations:** Categories (hasMany via wikidata_tracking_id), `charts` (belongsToMany via chart_wikidata_tracking).

---

### Chart

A named chart that combines one or more series into a single graph. Defined in the admin; displayed on the site show page in the “Combined charts” section of the matching group tab.

**Two modes:**
- **Per-site:** `site_id` set. Series come from directly attached categories (chart_category) for that site.
- **Template (all wikis):** `site_id` null. Series come from **WikidataTrackings** (chart_wikidata_tracking). On each wiki, the chart resolves to the category from each tracking for that site (one category per tracking per wiki), so the same chart definition appears on every wiki with the same series (e.g. “Men” + “Females” from two trackings).

| Attribute | Type | Description |
|-----------|------|-------------|
| id | PK | |
| site_id | FK → Site, nullable | null = template chart (all wikis); set = chart for that site only |
| name | string | Display name for the chart |
| slug | string | URL slug (unique per site, or unique among templates) |
| group | string, nullable | `maintenance`, `content`, or null; determines which tab the chart appears in |
| timestamps | | created_at, updated_at |

**Relations:** `site` (nullable), `categories` (belongsToMany via chart_category), `trackings` (belongsToMany via chart_wikidata_tracking with pivot sort_order, label, color).

**Pivot (chart_category):** sort_order, label, color (when using direct categories).

**Pivot (chart_wikidata_tracking):** sort_order, label, color (when using trackings; series resolved per site from each tracking’s category on that wiki).

---

### chart_category (pivot)

| Attribute | Type | Description |
|-----------|------|-------------|
| chart_id | FK → Chart | |
| category_id | FK → Category | |
| sort_order | int | Order of series in the chart |
| label | string, nullable | Override label for this series on the chart |
| color | string, nullable | Chart.js color (e.g. rgba(...)) |
| timestamps | | created_at, updated_at |

**Uniqueness:** (chart_id, category_id).

---

### chart_wikidata_tracking (pivot)

Links a chart to WikidataTrackings so the chart’s series are “one category per tracking per wiki” (template charts).

| Attribute | Type | Description |
|-----------|------|-------------|
| chart_id | FK → Chart | |
| wikidata_tracking_id | FK → WikidataTracking | |
| sort_order | int | Order of series |
| label | string, nullable | Override label for this series |
| color | string, nullable | Chart.js color |
| timestamps | | created_at, updated_at |

**Uniqueness:** (chart_id, wikidata_tracking_id).

---

### ArchiveItem

Represents a single Internet Archive item (e.g. a dump collection item).

| Attribute | Type | Description |
|-----------|------|-------------|
| id | PK | |
| identifier | string, unique | Archive identifier |
| publish_date | date, nullable | From metadata |
| last_sync | datetime, nullable | Last metadata sync |
| collection | json, nullable | |
| is_active | bool | Whether to process this item |
| timestamps | | created_at, updated_at |

**Relations:** `archive_files` (hasMany).

---

### ArchiveFile

A file belonging to an archive item (e.g. a SQL dump). Used to find and import historical site_stats.

| Attribute | Type | Description |
|-----------|------|-------------|
| id | PK | |
| archive_item_id | FK → ArchiveItem | |
| filename | string | File name in archive |
| last_sync | datetime, nullable | Set when historical import has been run |
| size | bigint, nullable | File size |
| dbname | string, nullable | Parsed from filename (e.g. prefix before first `-`) |
| timestamps | | created_at, updated_at |

**Relations:** `archive_item`.

**Usage:** GetHistoricalSiteinfoJob looks for files with filename like `%-%-%site_stats%`, downloads and imports the SQL, then fills Siteinfo from the temporary `site_stats` table and sets `last_sync` on the ArchiveFile.

---

### User

Standard Laravel user; used for auth and Filament panel access (FilamentUser).

## Indexes and Keys

- **category_counts:** category_id, date (for lookups by category and date range).
- **charts:** (site_id, slug) unique (nullable site_id for template charts).
- **chart_category:** (chart_id, category_id) unique.
- **chart_wikidata_tracking:** (chart_id, wikidata_tracking_id) unique.
- **siteinfos:** (site_id, info, date) for unique stats and lookups.
- **archive_files:** filename, archive_item_id; used with dbname for matching to Site.

## Lifecycle Summary

1. **Sites** are created when processing Wikidata sitelinks (`Site::parseUrl`) or when matching archive filenames (dbname).
2. **Categories** are created by GetWikidataTrackingJob from sitelinks (one per sitelink per tracking); type may be updated by GetCategoryCountsJob.
3. **CategoryCounts** are written by GetCategoryCountsJob (one row per category per day).
4. **Charts** and **chart_category** are created and updated in the Filament admin (Chart resource); no job writes them.
5. **Siteinfo** rows are written by GetSiteInfoJob (live) and GetHistoricalSiteinfoJob (from dumps).
6. **ArchiveItems** and **ArchiveFiles** are created/updated by GetArchiveMetadataJob from `ia metadata`.
