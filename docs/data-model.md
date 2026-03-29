# AGDC Data Model

The plugin now keeps legacy scrape tables intact and introduces a new operational schema for multi-region, multi-language, multi-store publishing.

## Legacy tables

These remain untouched and should be treated as read-only historical data:

- `{$wpdb->prefix}game_scraper_games`
- `{$wpdb->prefix}game_scraper_prices`
- `{$wpdb->prefix}game_scraper_rambouillet_posts`

They reflect the old Turkey-first / Steam-heavy model and should not drive new scraping or posting decisions.

## New operational tables

### `{$wpdb->prefix}agdc_stores`

Defines the commercial source:

- `steam`
- `epic`
- `gog`
- `humble`
- `fanatical`

### `{$wpdb->prefix}agdc_market_targets`

Defines the audience and publishing target:

- country
- language
- default currency
- site section / SEO segment

Examples:

- `tr-tr`
- `ro-ro`
- `es-es`
- `de-de`
- `fr-fr`
- `us-en`
- `gb-en`
- `global-en`

### `{$wpdb->prefix}agdc_games`

Canonical game record, store-independent:

- canonical name
- normalized name
- slug
- source IDs
- developer / publisher / artwork

### `{$wpdb->prefix}agdc_offers`

Current active offer per store / market / currency:

- discount offers
- free-game offers
- region code
- currency code
- language code
- current regular/sale price
- discount percent
- deeplink
- availability

`offer_type` distinguishes cases such as:

- `discount`
- `free_game`

### `{$wpdb->prefix}agdc_offer_snapshots`

Historical price/availability snapshots for an offer:

- useful for change tracking
- useful for SEO freshness and editorial logic
- keeps raw payload if needed

### `{$wpdb->prefix}agdc_generated_posts`

Maps offers and games to generated WordPress posts by market/language/content kind.

### `{$wpdb->prefix}agdc_runs`

Tracks scraper/import runs:

- hourly
- daily
- manual

This is the right place for diagnostics and future reporting.

## Why this model

This structure supports:

- multiple stores in one region
- multiple currencies in one region
- expansion beyond Turkey
- localized publishing targets
- discount and free-game flows in the same system
- cleaner SEO-oriented content generation
