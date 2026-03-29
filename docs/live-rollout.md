# Live Rollout

This plugin now assumes a market-first publishing model with WPML language variants such as:

- `tr-tr`
- `en-gb`
- `en-us`
- `es-es`
- `es-mx`

## Before deploy

1. Deploy the latest plugin build to production.
2. Ensure the same WPML language variants exist on the live site.
3. Flush rewrites after deploy:

```bash
wp rewrite flush
```

4. Confirm the live site has the expected market targets in plugin settings.

## Backfill existing AGDC content

Use the included script to normalize:

- `_agdc_market_key`
- `_agdc_language_code`
- `_agdc_site_section`
- localized categories
- localized tags

Run it with WP-CLI:

```bash
wp eval-file wp-content/plugins/auto-games-discount-creator/bin/backfill-market-seo.php
```

## After backfill

Check these pages manually:

- one `agdc_roundup` page in each market
- one `free_game` post

Verify:

- canonical
- hreflang
- localized category/tag
- correct market-prefixed URL
- correct currency in the rendered content

## Recommended rollout order

1. Deploy plugin
2. Flush rewrites
3. Run backfill script
4. Check `tr-tr`
5. Check `en-us`
6. Check one Spanish market
7. Re-enable daily/hourly automation if paused
