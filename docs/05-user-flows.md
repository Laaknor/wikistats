# User Flows

## Public User Flows

### Browse sites and categories

1. User visits the site root → redirected to `site.index` (site list).
2. **Site list** (`SiteController@index`): Lists all sites, ordered by family and hostname. Each site is a link to its show page.
3. **Site show** (`SiteController@show`, `site/{site:hostname}`): Shows the site with **tabs** by group (Maintenance, Content, Other). The active tab is reflected in the URL as `?tab=maintenance`, `?tab=content`, or `?tab=other`, so refreshing or sharing the link keeps the same tab. Each tab contains:
   - **Combined charts:** Cards for each defined Chart (multiple categories in one graph). Each card has a small embedded chart (iframe to `chart.small`) and links to the full-size chart page.
   - **Single category counts:** One card per Category (same as before): display name, small embedded chart (iframe to `graph.small`), link to full-size graph.
4. **Graph (large)** (`GraphController@show`, `site/{site:hostname}/graph/{graph:name}`): Full-size interactive line chart of a single category’s monthly count over time. The graph name is the category name (URL-encoded).
5. **Graph (small)** (`GraphController@showSmall`): Same data as a small Chart.js chart, used in iframes on the site show page for single-category cards.
6. **Chart (large)** (`GraphController@showChart`, `site/{site:hostname}/chart/{chartSlug}`): Full-size interactive line chart with **multiple series** (one per category in the chart). Used for defined “combined” charts.
7. **Chart (small)** (`GraphController@showSmallChart`, `site/{site:hostname}/chart-small/{chartSlug}`): Same multi-series data as a small chart, used in iframes for combined chart cards.
8. **Graph (image)** (`GraphController@showSmallImage`): Returns a PNG image of a simple line chart for a single category (e.g. for embedding or previews). Uses GD and monthly-averaged data.

**URL handling:** Graph routes accept the category name as `graph`; the controller decodes it and looks up the category by name (with fallback for single/double encoding). Chart routes use the chart’s `slug` (unique per site). Route model binding uses `site:hostname`.

### Other public routes

- **About** (`/about`): Static page (StaticPageController).
- **Auth:** Standard Laravel auth routes (login, register, password reset, email verification) from `routes/auth.php`.
- **Dashboard / profile:** Authenticated user dashboard and profile views (middleware `auth` / `verified`).

---

## Admin Flows (Filament)

Access to the Filament admin panel is restricted to users that implement `FilamentUser` and are allowed to access the panel (e.g. `canAccessPanel()`).

### Manage Wikidata trackings

1. Admin opens the Filament panel (path configured in `AdminPanelProvider`).
2. **List** (`ListWikidataTrackings`): Table of Wikidata trackings with columns: name, item, type, group, last_sync (and optional created_at/updated_at). Actions: edit. Bulk: delete.
3. **Create** (`CreateWikidataTracking`): Form with:
   - **Item** (required): Wikidata item ID (e.g. Q12345).
   - **Type** (required): e.g. “Category Count” (stored as `categorycount`); used when creating categories from sitelinks.
   - **Group** (optional): “Maintenance”, “Content”, or empty. Used to group categories into tabs on the site show page.
   - **Name** (optional).
   - **Description** (optional, textarea).
4. **Edit** (`EditWikidataTracking`): Same form for an existing tracking.

After save, the tracking is picked up by **GetWikidataTrackingJob** on the next run (when the tracking is due for sync). That job creates/updates Site and Category records from the item’s sitelinks. **GetCategoryCountsJob** then refreshes counts for those categories according to the schedule.

### Manage Charts (combined charts)

1. **List** (`ListCharts`): Table of charts with columns: site (or “All wikis”), name, slug, group, series source (trackings or categories). Actions: edit. Bulk: delete.
2. **Create** (`CreateChart`): Form with **site** (optional), name, slug, group (optional).
   - **Leave site empty** to create a **template chart** that appears on every wiki: add **Trackings (series from Wikidata)** (e.g. “Men” + “Females” trackings). On each wiki the chart shows one series per tracking (the category from that tracking on that wiki). No separate graph per tracking—one combined chart everywhere.
   - **Set a site** for a chart on a single wiki: use **Trackings** (same resolution) or **Categories** to attach specific categories for that site.
3. **Edit** (`EditChart`): Same form plus:
   - **Trackings (series from Wikidata):** Attach WikidataTrackings with optional pivot sort_order, label, color. Use this for “combine Men + Females (or other trackings) into one chart.” Template charts and per-site charts can both use trackings.
   - **Categories (series):** Attach categories (scoped to the chart’s site) with optional pivot. Use when you want to pick specific categories on one site instead of resolving from trackings.

Charts appear on the site show page in the “Combined charts” section of the matching group tab: per-site charts when they belong to that site; template charts (site empty) when the wiki has at least one category from the chart’s trackings.

### Other admin capabilities

- User management and panel access are handled by Laravel and Filament (e.g. User model, policies). Custom Filament resources: Wikidata trackings, Charts.
- Horizon dashboard (if mounted) allows viewing queues and failed jobs; configuration is in Horizon and the Filament panel.

---

## Data Flow Summary

1. **Admin** creates a Wikidata tracking (item ID + type).
2. **GetWikidataTrackingJob** (hourly, when due) fetches sitelinks and creates/associates Sites and Categories.
3. **GetCategoryCountsJob** (every minute, when there are stale active categories) updates one active category per run: fetches categoryinfo (and optionally subcategory counts), writes CategoryCount for today, updates category type and last_sync. If the wiki page no longer exists, the category is marked inactive (`is_active = false`) and no longer synced.
4. **GetSiteInfoJob** (every 5 minutes, when there are stale sites) updates one site’s current statistics into Siteinfo.
5. **Public user** browses sites → site show page (tabs by group) → combined chart cards and single-category cards with small charts and links to full graph/chart pages; graph and chart pages read CategoryCount data and render single- or multi-series charts (Chart.js or GD image).

Archive flow:

6. **ArchiveItem** and **ArchiveFile** records exist (e.g. created or seeded).
7. **GetArchiveMetadataJob** (every 5 minutes, when there are unsynced items) runs `ia metadata` and fills ArchiveFile (and publish_date) for up to 2 items.
8. **GetHistoricalSiteinfoJob** (yearly, when there are unprocessed site_stats files) downloads one dump, imports SQL, backfills Siteinfo from `site_stats`, then cleans up.

All of the above (except the manual “create tracking” step) are driven by the scheduler and queue workers.
