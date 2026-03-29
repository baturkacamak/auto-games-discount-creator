# Market Rollout Notes

The scraper source market and the publishing target are intentionally separate.

## Source market

Configured under `source`:

- `itad_country_code`
- `itad_currency_code`
- auto-bootstrapped anonymous ITAD session

This controls which offers ITAD returns.

Examples:

- `TR` + `TRY`: Turkey-facing pricing when ITAD supports it
- `ES` + `EUR`: Euro market
- `US` + `USD`: US/global baseline

## Publishing target

Configured under `data_model.default_market_target_key`.

This controls:

- post title language
- post slug prefix
- post meta for market/language
- generated post bookkeeping

Examples:

- `tr-tr`: Turkish copy, `tr` section
- `es-es`: Spanish copy, `es` section
- `global-en`: English copy, `global` section

## Recommended rollout

### `tr-tr`

- source: `TR` / `TRY`
- publishing target: `tr-tr`
- use for Turkey-focused editorial and SEO pages

### `es-es`

- source: `ES` / `EUR`
- publishing target: `es-es`
- useful for testing in the current local environment because anonymous ITAD session bootstrap tends to land on `ES`

### `global-en`

- source: `US` / `USD`
- publishing target: `global-en`
- good baseline for broader English-language content

## Verified payload presets

The defaults in `settings.json` now use the working `browse -> deals/api/list -> deals/api/games -> deals/api/prices` flow.

Current baseline presets:

- `daily_verified_under_1_historical_low`
- `daily_verified_under_5_historical_low`
- `daily_store_low_multi_store_quality`
- `hourly_free_games_multi_store`

These are intended as stable starting points. Expand them after validating output quality for each target market.
