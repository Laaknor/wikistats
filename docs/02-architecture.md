# Architecture

## High-Level Structure

Maintenalyzer is a Laravel application with:

- **Web layer** — Controllers and Blade views for public pages (site list, site detail with tabs and combined charts, graph/chart views).
- **Admin layer** — Filament panel for managing Wikidata trackings (with group) and Charts (combined multi-series charts).
- **Job layer** — Queued jobs that call external APIs and update the database; scheduled via Laravel’s scheduler.
- **Queue worker** — Laravel Horizon for running and monitoring queue workers.

```
┌─────────────────────────────────────────────────────────────────┐
│                        Wikistats Application                      │
├─────────────────────────────────────────────────────────────────┤
│  Web (public)        │  Admin (Filament)   │  Console / Scheduler  │
│  - SiteController    │  - WikidataTracking │  - GetCategoryCounts │
│  - GraphController   │    Resource          │  - GetSiteInfo        │
│  - StaticPageController │  - Chart Resource   │  - GetWikidataTracking │
│                      │                      │  - GetArchiveMetadata   │
│                      │                      │  - GetHistoricalSiteinfo │
├─────────────────────────────────────────────────────────────────┤
│  Queue (Horizon)     │  Jobs run async, call APIs, write to DB    │
├─────────────────────────────────────────────────────────────────┤
│  Database (MySQL etc.)  │  Sites, Categories, CategoryCounts, Charts,  │
│  chart_category, Siteinfos, WikidataTrackings, ArchiveItems, ArchiveFiles │
└─────────────────────────────────────────────────────────────────┘
         │                    │
         ▼                    ▼
  MediaWiki API          Wikidata API
  (per-site api.php)     (wikidata.org)
         │
         ▼
  Internet Archive (ia metadata / ia download)
```

## Directory Layout (Relevant Parts)

| Path | Role |
|------|------|
| `app/Http/Controllers/` | SiteController, GraphController, StaticPageController, TestController |
| `app/Jobs/` | GetCategoryCountsJob, GetSiteInfoJob, GetWikidataTrackingJob, GetArchiveMetadataJob, GetHistoricalSiteinfoJob, GetSiteMatrixJob |
| `app/Models/` | Site, Category, CategoryCount, Chart, Siteinfo, WikidataTracking, ArchiveItem, ArchiveFile, User |
| `app/Filament/Resources/WikidataTrackings/` | Filament resource for Wikidata trackings (CRUD, table, form, group) |
| `app/Filament/Resources/Charts/` | Filament resource for Charts (CRUD, categories relation manager with pivot) |
| `app/Livewire/` | ShowCategoryGraph, auth-related components |
| `routes/web.php` | Public routes (sites, graphs, about, auth) |
| `routes/console.php` | Scheduler: job and command schedules |
| `config/horizon.php` | Horizon queue config |

## External Integrations

1. **MediaWiki API** (`{site.url}w/api.php`)
   - `action=query&prop=categoryinfo&titles=...` — category page/subcat counts.
   - `action=query&list=categorymembers&cmtype=subcat&...` — list subcategories.
   - `action=query&meta=siteinfo&siprop=statistics` — site-level stats.

2. **Wikidata**
   - REST: `https://wikidata.org/w/rest.php/wikibase/v1/entities/items/{id}?_fields=sitelinks` — sitelinks for a tracked item.

3. **Internet Archive**
   - CLI: `ia metadata {identifier}`, `ia download {identifier} {filename}` — list and download dump files; used for historical site_stats SQL.

## Security and Concurrency

- **API rate limiting:** GetCategoryCountsJob uses `sleep(2)` between batched subcategory requests to avoid overloading wiki APIs.
- **Admin access:** Filament admin is protected; only users that pass `FilamentUser` (e.g. `canAccessPanel`) can manage Wikidata trackings.
- **Queue:** Jobs are queued so that heavy work (API calls, archive downloads) runs in the background and does not block the web or scheduler.

## Dependencies

- Laravel 13 (core, queue, scheduler, HTTP client).
- Filament 5 (admin panel) on Livewire 4.
- Horizon (queue monitoring).
- Chart.js / IcehouseVentures Laravel Chart.js 5 (graphs).
- PHP GD (for small chart image endpoint).
- Internet Archive CLI (`ia`) for archive metadata and downloads.
- Frontend build: Vite 8, Tailwind CSS 4.
