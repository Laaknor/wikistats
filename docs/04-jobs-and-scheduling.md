# Jobs and Scheduling

All background work is implemented as Laravel jobs (implementing `ShouldQueue`) and scheduled in `routes/console.php`. Horizon runs the queue workers.

## Schedule Summary

| Schedule | Job / Command | Condition |
|----------|----------------|-----------|
| Every minute | `GetCategoryCountsJob` | At least one active Category with `last_sync` &lt; 7 days ago |
| Every 5 minutes | `GetSiteInfoJob` | At least one Site with `last_siteinfo` &lt; 7 days ago or null |
| Every 5 minutes | `GetArchiveMetadataJob` | At least one ArchiveItem with `is_active` and `last_sync` null |
| Every 5 minutes | `horizon:snapshot` | Always |
| Hourly | `GetWikidataTrackingJob` | At least one WikidataTracking with `last_sync` &lt; 7 days ago |
| Yearly | `GetHistoricalSiteinfoJob` | At least one ArchiveFile matching `%-%-%site_stats%` with `last_sync` null |

Conditions use `when()` so the job is only dispatched when there is work to do.

---

## GetCategoryCountsJob

**Purpose:** Refresh category page counts (or subcategory-sum counts) for categories that have not been synced in the last 7 days.

**Logic:**

1. Select active categories (`is_active = true`) where `last_sync < now() - 7 days`, in random order, limit 1.
2. For the chosen category, call the site’s API: `action=query&prop=categoryinfo&titles={wikiApiTitle}` (URL-encoded, decoded from stored `name`).
3. From the response:
   - If the page is **missing** on the wiki (no `pageid` in the API page object), log a warning, set `is_active = false`, update `last_sync`, and skip further API calls for that category.
4. Otherwise from the response:
   - Read `subcats` and `pages`. If `subcats > pages`, set category type to `subcategorycount`; else `categorycount`.
   - **categorycount:** store/update `CategoryCount` for today with `categoryinfo.pages`.
   - **subcategorycount:** call `list=categorymembers&cmtype=subcat`, then in batches of 10 subcategory titles call `prop=categoryinfo&titles=...`, sum the `pages` values, then store/update one `CategoryCount` for today with that sum. Sleep 2 seconds between batch requests.
5. Update category’s `mw_category_id`, `display_name`, and `last_sync` (only when the page exists).

**Rate limiting:** `sleep(2)` between subcategory batch API calls.

**Models:** Category, CategoryCount, Site (via category).

---

## GetSiteInfoJob

**Purpose:** Fetch current site statistics from the MediaWiki API and store them as Siteinfo rows for today.

**Logic:**

1. Select sites where `last_siteinfo` is null or &lt; 7 days ago, ordered by `last_siteinfo` ascending, limit 1.
2. Request `{site.url}w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json`.
3. For each statistic in the allowlist (`pages`, `users`, `admins`, `images`, `edits`, `articles`, `activeusers`), `updateOrCreate` Siteinfo (site_id, info, date=today, count).
4. Set `site.last_siteinfo = now()` and save.

**Models:** Site, Siteinfo.

---

## GetWikidataTrackingJob

**Purpose:** For one Wikidata tracking that has not been synced in the last 7 days, fetch sitelinks and ensure corresponding Site and Category records exist.

**Logic:**

1. Select one WikidataTracking where `last_sync < now() - 1 day` (schedule runs hourly when any tracking has `last_sync` &lt; 7 days), ordered by `last_sync` ascending.
2. GET `https://wikidata.org/w/rest.php/wikibase/v1/entities/items/{item}?_fields=sitelinks`.
3. For each sitelink: parse URL with `Site::parseUrl(sitelink.url)`, then `Category::firstOrCreate` by (site_id, wikidata_tracking_id, name), with type from the tracking’s `type` on create.
4. Set `wikidata_tracking.last_sync = now()` and save.

**Models:** WikidataTracking, Site, Category.

---

## GetArchiveMetadataJob

**Purpose:** For archive items that have never been synced, fetch metadata from the Internet Archive and create/update ArchiveFile records.

**Logic:**

1. Select up to 2 active ArchiveItems where `last_sync` is null, in random order.
2. For each: run `ia metadata {identifier}` (CLI), decode JSON.
3. For each file in `metadata.files`: `updateOrCreate` ArchiveFile by (filename, archive_item_id), setting size and dbname (parsed from filename).
4. Set `archive_item.last_sync`, `archive_item.publish_date` from metadata and save.

**Dependency:** Internet Archive CLI (`ia`) must be installed and configured.

**Models:** ArchiveItem, ArchiveFile.

---

## GetHistoricalSiteinfoJob

**Purpose:** Import historical site statistics from an archive file that contains a `site_stats` SQL dump.

**Logic:**

1. Select one ArchiveFile where filename matches `%-%-%site_stats%`, `last_sync` is null, and `dbname` is in the set of existing Site dbnames, in random order.
2. Get the parent ArchiveItem; run `ia download {identifier} {filename}` to a temp directory.
3. If the file is gzipped, gunzip to a temporary SQL file.
4. Import the SQL file into the application database (creates or fills `site_stats` table).
5. Read from `site_stats` (e.g. `ss_total_edits`, `ss_active_users`, `ss_total_pages`); map to Site by dbname (from filename); create/update Siteinfo rows for that site and the archive item’s `publish_date` for `edits`, `activeusers`, `pages`.
6. Set `archive_file.last_sync`, delete temp directory, drop `site_stats` table.

**Dependency:** `ia` CLI, `mysql` client for import. Database must accept the dump’s schema.

**Models:** Site, ArchiveItem, ArchiveFile, Siteinfo.

---

## wikistats:getcategorycount

**Purpose:** Manually import historical category page counts from archive `categorylinks` and `page` SQL dumps.

**Logic:**

1. Select one unsynced ArchiveFile where filename matches `%-%-%categorylinks%.sql.gz`, `dbname` matches an existing Site, and a matching `%-page.sql.gz` ArchiveFile exists for the same archive item and dbname. If categorylinks dumps remain but none have a page dump, warn and exit without downloading.
2. Download both dumps, then confirm both local files exist before importing either. If either file is missing, skip import and do not mark the ArchiveFile synced.
3. Import both SQL dumps, then for each Category on the matching Site:
   - Convert the category display title after the namespace prefix to the MediaWiki dump format (spaces become underscores).
   - **categorycount:** count matching `categorylinks` rows with `cl_type = page`.
   - **subcategorycount:** find subcategory page titles through `categorylinks` and `page`, then count page rows linked to those subcategories.
4. Store/update CategoryCount rows for the ArchiveItem publish date, mark the ArchiveFile synced, clean up temp files, and drop the imported `categorylinks` and `page` tables.

**SQL safety:** Category names are passed as bound query parameters so names containing apostrophes or other SQL-significant characters are handled correctly.

**Dependency:** `ia` CLI, `zcat`, and `mysql` client for import. Database must accept the dump’s schema.

**Models:** Site, Category, CategoryCount, ArchiveItem, ArchiveFile.

---

## GetSiteMatrixJob

Present in the codebase; not referenced in `routes/console.php`. Likely intended for populating or updating sites from the MediaWiki site matrix; not part of the current schedule.

---

## Horizon

- Horizon runs the queue workers that execute these jobs.
- `horizon:snapshot` runs every 5 minutes to collect metrics.
- Queue configuration is in `config/horizon.php`; failed jobs can be inspected and retried from the Horizon dashboard (when enabled).
