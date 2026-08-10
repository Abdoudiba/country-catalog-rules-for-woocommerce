# Geo Catalog for WooCommerce

Restrict which products (or entire categories) are visible or purchasable
based on the visitor's country — without adding a second geolocation system
to a store that already has one.

## Why this exists

Most WooCommerce country-restriction plugins bring their own IP lookup. If
the store already has WooCommerce's built-in geolocation active (the MaxMind
GeoIP2 database WooCommerce ships and uses for shipping/tax/currency), a
second, independent IP lookup can disagree with it — a visitor gets flagged
as one country by WooCommerce's own logic and a different one by the plugin,
especially near a border or behind a VPN/proxy. This plugin deliberately
reuses `WC_Geolocation` instead of introducing a second source of truth.

Built originally to solve one specific case (a Senegal-based store expanding
sales to Côte d'Ivoire, needing per-country catalog visibility with zero
existing infrastructure for it), but designed generically from the start —
nothing in the code assumes a specific country pair or store.

## What it does (v1)

- **Per-category rule**: restrict a whole product category to a list of
  countries in one place, instead of configuring every product individually.
- **Per-product override**: any product can override its category's rule
  (or set its own, if uncategorized/unrestricted otherwise).
- **Two restriction modes**:
  - *Hide completely* — not in the shop, search, related-product widgets, or
    (via a direct-URL check, not just the standard visibility filter) even a
    direct link to the product page.
  - *Show, mark unavailable* — the product stays visible and indexable, but
    the add-to-cart button is disabled and a configurable message shown
    instead.
- **Admin preview**: `?wgc_preview_country=CI` on any storefront URL lets a
  logged-in shop manager preview the site as a visitor from that country,
  without a VPN. Query-param only, never written to a cookie/session, so it's
  obviously inert for real visitors and leaves no persistent state to forget
  about.
- Failing open on unknown location: if the visitor's country can't be
  determined, restricted products stay visible rather than risk hiding
  products from real customers because of a geolocation miss.

## Installation

1. Requires WooCommerce active (the plugin no-ops with an admin notice if
   WooCommerce isn't installed — it never fatal-errors on missing WC classes).
2. Upload the plugin folder to `wp-content/plugins/`, or zip it and use
   Plugins → Add New → Upload Plugin.
3. Activate.
4. Configure category rules under **Products → Categories** (edit any
   category — new fields appear at the bottom of the edit screen).
5. Configure product-level overrides under **Products → [edit a product] →
   Product data → Geo Catalog** tab.
6. Optional: **WooCommerce → Settings → Geo Catalog** to customize the
   "unavailable" message shown on blocked products.

## Architecture

```
woo-geo-catalog.php              Plugin bootstrap, WooCommerce dependency check, HPOS compat declaration
includes/
  class-wgc-geolocation.php      Visitor country resolution (wraps WC_Geolocation) + admin preview override
  class-wgc-rules.php            Resolves the effective rule for a product: product override > category > none
  class-wgc-product-fields.php   Product Data panel UI (per-product override)
  class-wgc-category-fields.php  Category edit-screen UI (per-category rule)
  class-wgc-visibility.php       Enforcement: hooks into WooCommerce's visibility/purchasability filters
  class-wgc-settings.php         WooCommerce → Settings → Geo Catalog tab (shared settings, e.g. message text)
```

Data storage: plain post meta (`_wgc_countries`, `_wgc_mode`,
`_wgc_override_category` on products) and term meta (`wgc_countries`,
`wgc_mode` on `product_cat` terms) — no custom database tables, so it's
inspectable/editable directly if ever needed, and there's nothing extra to
clean up on uninstall beyond standard meta.

## Known limitations / v1 scope boundaries

These are deliberate v1 scope cuts, not oversights — documenting them here so
future work (mine or a buyer's) doesn't have to rediscover them:

- **Product feeds (Meta/Google/TikTok catalog sync)**: this plugin hooks into
  WooCommerce's standard `woocommerce_product_is_visible` filter, which is
  the correct, idiomatic way to affect catalog visibility — but it has *not*
  been verified against every third-party feed-generator plugin. If a feed
  plugin queries products by a path that bypasses that filter, a
  country-restricted product could still appear in an ad feed shown to the
  wrong country. Worth an explicit test pass with whichever feed plugins are
  active before relying on this for compliance-sensitive restrictions.
- **No bulk CSV import/export of rules.** Category-level rules already give
  bulk control without needing this, but it's a natural v2 addition if
  managing very granular per-product exceptions at scale.
- **No state/region-level restriction**, only country. Some competing paid
  plugins support this; not implemented here (v1 scope was matching core
  country-level competitor behavior first, see project history for why).
- **Single-site tested only** so far — no multisite-specific handling.
- **No REST API endpoints** for managing rules programmatically (e.g. from
  an external tool). Everything is admin-UI-driven in v1.

## Ideas for later (not built, just captured)

- A bulk "apply to N products by category/tag" action, closer to what the
  Pro tiers of competing plugins charge for.
- REST API exposure of rule management, for headless/automation use cases.
- A "why is this hidden?" admin diagnostic showing the resolved rule +
  detected country for any given product/visitor combination, beyond the
  current preview-banner approach.
- Verified, documented compatibility notes for the major feed plugins
  (Meta for WooCommerce, Google for WooCommerce, TikTok).

## License

GPL v2 or later, matching the plugin header — standard for WordPress plugin
distribution regardless of how this project itself ends up distributed.
