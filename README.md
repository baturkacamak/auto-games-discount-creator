# Auto Games Discount Creator

`auto-games-discount-creator` is a custom WordPress plugin for sourcing game deals and free games, storing normalized offer data, and publishing market-aware content for `uciki.com`.

The plugin currently focuses on:

- IsThereAnyDeal-powered deal discovery
- daily discount roundups
- free-game posts
- multi-store offer normalization
- market/language-aware publishing
- dynamic snapshot-backed roundup pages

## What Changed

This repository is no longer just a simple “scrape and publish” plugin.

It now includes:

- a normalized `agdc_*` data model for stores, games, offers, snapshots, runs, and generated posts
- a native WordPress settings screen instead of ad-hoc config-only behavior
- runtime/debug state in wp-admin
- ITAD session bootstrap logic
- store URL normalization and redirect cleanup
- store/CDN image resolution strategies
- score/review enrichment for roundup cards
- dynamic `agdc_roundup` pages backed by stored snapshots instead of `post_content`

## Architecture

High-level flow:

1. Source payloads are loaded from plugin settings.
2. ITAD data is fetched and normalized into `agdc_*` tables.
3. Daily and free-game flows are selected separately.
4. Publishing creates:
   - standard posts for free games
   - `agdc_roundup` entries for daily deal pages
5. Daily roundup pages render from `_agdc_snapshot_payload` at request time.

Key components:

- `inc/Core/Settings/SettingsRepository.php`
- `inc/Core/Settings/MarketTargetRepository.php`
- `inc/Core/Settings/RuntimeStateRepository.php`
- `inc/Core/Utility/Scraper.php`
- `inc/Core/Utility/GameInformationDatabase.php`
- `inc/Core/Utility/OfferSelectionService.php`
- `inc/Core/Utility/OfferImageResolver.php`
- `inc/Core/Utility/GameReviewLookup.php`
- `inc/Modules/AdminSettingsModule.php`
- `inc/Modules/SetupModule.php`
- `inc/Modules/ScheduleModule.php`
- `inc/Modules/RoundupModule.php`
- `inc/Post/Poster.php`
- `inc/Post/Strategy/DailyPostStrategy.php`
- `inc/Post/Strategy/FreeGamesPostStrategy.php`
- `inc/Post/DailyRoundupSnapshotRenderer.php`

## Data Model

The plugin keeps legacy tables intact but now uses normalized `agdc_*` tables for active operation.

See:

- `docs/data-model.md`

Core tables include:

- `agdc_stores`
- `agdc_market_targets`
- `agdc_games`
- `agdc_offers`
- `agdc_offer_snapshots`
- `agdc_generated_posts`
- `agdc_runs`

## Publishing Model

### Daily roundups

- stored as `agdc_roundup`
- URL is still a normal WordPress permalink
- visual content is rendered dynamically from snapshot data
- `post_content` is no longer the source of truth

### Free games

- published as regular WordPress posts
- triggered independently from the daily roundup flow

## Admin Features

The plugin includes a native settings page under WordPress admin.

Current admin features:

- general plugin settings
- source payload management
- market target defaults
- separate posting settings for daily and free-game flows
- runtime summaries
- test actions
- manual run actions
- draft cleanup helpers

## Source Notes

The current source implementation is centered on IsThereAnyDeal.

Important behavior:

- ITAD session bootstrap is automatic
- redirects are resolved to canonical store URLs
- tracking query strings are removed
- store image resolution prefers known store/CDN patterns
- Epic uses catalog lookup where possible and falls back when required

## Local Theme Note

This repository is the plugin only.

Theme-side rendering and styling changes used by `uciki.local` live outside this repository, inside the site theme.

## Docs

- `docs/data-model.md`
- `docs/market-rollout.md`
