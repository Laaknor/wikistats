# Maintenalyzer — System Overview

## Purpose

**Maintenalyzer** is a web application that collects, stores, and visualizes statistics from Wikimedia projects (e.g. Wikipedia, Wiktionary). Its main purpose is to:

1. **Track category sizes over time** — For selected categories on wiki sites, the system periodically fetches page counts (or summed subcategory page counts), stores them by date, and exposes them as time-series graphs.

2. **Track site-level statistics** — For known wiki sites, it fetches and stores siteinfo statistics (pages, users, edits, articles, etc.) both from the live MediaWiki API and from historical dumps (e.g. Internet Archive).

3. **Provide a public-facing dashboard** — Users can browse sites, see which categories are tracked, and view interactive or embedded charts of category growth over time.

4. **Support administration of tracked items** — Administrators define what to track (e.g. Wikidata items whose sitelinks become categories to monitor) and the system keeps data up to date via background jobs.

The system is built to work with the **MediaWiki API** and **Wikidata** for discovery and live data, and with the **Internet Archive** for historical site statistics when available.

## Key Capabilities

| Capability | Description |
|------------|-------------|
| **Category count sync** | For each tracked category, fetches `categoryinfo` (and optionally subcategory members and sums their pages), determines whether to use direct page count or subcategory sum, and stores daily counts. |
| **Site info sync** | For each site, fetches current statistics (pages, users, edits, etc.) from the site’s `api.php?action=query&meta=siteinfo&siprop=statistics` and stores them by date. |
| **Wikidata-driven discovery** | Administrators create “Wikidata trackings” by item ID. The system fetches sitelinks from the Wikidata API, creates or finds `Site` and `Category` records per sitelink, and keeps categories in sync. |
| **Historical siteinfo** | For archive items (e.g. from Internet Archive) that contain `site_stats` SQL dumps, the system can download and import them and backfill historical `Siteinfo` rows. |
| **Charts and embedding** | Category time-series are exposed as large and small charts (e.g. for detail and embed), with an optional image endpoint for simple PNG charts. |


## Technology Context

- **Framework:** Laravel (PHP)
- **Admin UI:** Filament
- **Queue:** Laravel queues (Horizon for supervision)
- **Charts:** Chart.js (via `icehouseventures/laravel-chartjs`), plus a simple GD-based image endpoint for small charts
- **External APIs:** MediaWiki `api.php`, Wikidata REST (`wikibase/v1/entities/items/…`), Internet Archive CLI (`ia metadata`, `ia download`)

See [02-architecture.md](02-architecture.md) for high-level structure, [03-data-model.md](03-data-model.md) for entities and relationships, [04-jobs-and-scheduling.md](04-jobs-and-scheduling.md) for background jobs, and [05-user-flows.md](05-user-flows.md) for user and admin flows.
