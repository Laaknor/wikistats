# User Flows

## Public User Flows

### Browse sites and categories

1. User visits the site root → redirected to `site.index` (site list).
2. **Site list** (`SiteController@index`): Lists all sites, ordered by family and hostname. Each site is a link to its show page.
3. **Site show** (`SiteController@show`, `site/{site:hostname}`): Shows the site and its categories. Each category is a card with:
   - Display name (or category name).
   - A small embedded chart (iframe to `graph.small`).
   - Link to the full-size graph page.
4. **Graph (large)** (`GraphController@show`, `site/{site:hostname}/graph/{graph:name}`): Full-size interactive line chart of the category’s monthly count over time. The graph name is the category name (URL-encoded).
5. **Graph (small)** (`GraphController@showSmall`): Same data as a small Chart.js chart, used in iframes on the site show page.
6. **Graph (image)** (`GraphController@showSmallImage`): Returns a PNG image of a simple line chart for the category (e.g. for embedding or previews). Uses GD and monthly-averaged data.

**URL handling:** Graph routes accept the category name as `graph`; the controller decodes it and looks up the category by name (with fallback for single/double encoding). Route model binding uses `site:hostname`.

### Other public routes

- **About** (`/about`): Static page (StaticPageController).
- **Auth:** Standard Laravel auth routes (login, register, password reset, email verification) from `routes/auth.php`.
- **Dashboard / profile:** Authenticated user dashboard and profile views (middleware `auth` / `verified`).

---

## Admin Flows (Filament)

Access to the Filament admin panel is restricted to users that implement `FilamentUser` and are allowed to access the panel (e.g. `canAccessPanel()`).

### Manage Wikidata trackings

1. Admin opens the Filament panel (path configured in `AdminPanelProvider`).
2. **List** (`ListWikidataTrackings`): Table of Wikidata trackings with columns: name, item, type, last_sync (and optional created_at/updated_at). Actions: edit. Bulk: delete.
3. **Create** (`CreateWikidataTracking`): Form with:
   - **Item** (required): Wikidata item ID (e.g. Q12345).
   - **Type** (required): e.g. “Category Count” (stored as `categorycount`); used when creating categories from sitelinks.
   - **Name** (optional).
   - **Description** (optional, textarea).
4. **Edit** (`EditWikidataTracking`): Same form for an existing tracking.

After save, the tracking is picked up by **GetWikidataTrackingJob** on the next run (when the tracking is due for sync). That job creates/updates Site and Category records from the item’s sitelinks. **GetCategoryCountsJob** then refreshes counts for those categories according to the schedule.

### Other admin capabilities

- User management and panel access are handled by Laravel and Filament (e.g. User model, policies). The only custom Filament resource described here is Wikidata trackings.
- Horizon dashboard (if mounted) allows viewing queues and failed jobs; configuration is in Horizon and the Filament panel.

---

## Data Flow Summary

1. **Admin** creates a Wikidata tracking (item ID + type).
2. **GetWikidataTrackingJob** (hourly, when due) fetches sitelinks and creates/associates Sites and Categories.
3. **GetCategoryCountsJob** (every minute, when there are stale categories) updates one category per run: fetches categoryinfo (and optionally subcategory counts), writes CategoryCount for today, updates category type and last_sync.
4. **GetSiteInfoJob** (every 5 minutes, when there are stale sites) updates one site’s current statistics into Siteinfo.
5. **Public user** browses sites → site show page → category cards with small charts and links to full graphs; graph pages read CategoryCount data and render charts (Chart.js or GD image).

Archive flow:

6. **ArchiveItem** and **ArchiveFile** records exist (e.g. created or seeded).
7. **GetArchiveMetadataJob** (every 5 minutes, when there are unsynced items) runs `ia metadata` and fills ArchiveFile (and publish_date) for up to 2 items.
8. **GetHistoricalSiteinfoJob** (yearly, when there are unprocessed site_stats files) downloads one dump, imports SQL, backfills Siteinfo from `site_stats`, then cleans up.

All of the above (except the manual “create tracking” step) are driven by the scheduler and queue workers.
