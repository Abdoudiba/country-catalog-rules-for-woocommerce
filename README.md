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
geo-catalog-for-woocommerce.php              Plugin bootstrap, WooCommerce dependency check, HPOS compat declaration
includes/
  class-wgc-geolocation.php      Visitor country resolution (wraps WC_Geolocation) + admin preview override
  class-wgc-rules.php            Resolves the effective rule for a product: product override > product's own category > inherited from an ancestor category > none
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

## Tested against a live install

v1 (`0999064`) was built without a test WooCommerce site available. A first
real-install pass on `test.yuupee.com` (browser-driven, not just `php -l`)
confirmed the admin UI end-to-end — category fields, product override tab,
settings tab all render, save, and persist correctly — and confirmed the
core hide/preview/banner mechanics work correctly for a product directly
assigned to a restricted category. It also surfaced two real bugs, both
fixed in 0.1.1 and confirmed fixed on a second real-install pass on
`test.yuupee.com`:

- **Category rules didn't cascade to subcategories.** Restricting a parent
  category (e.g. "Informatiques") had no effect on a product only assigned
  to a child category (e.g. "Câbles / Adaptateurs") underneath it — the
  opposite of what a shop owner setting a category-level rule would expect.
  `WGC_Rules::resolve_for_product()` only checked the product's *own*
  directly-assigned category terms, never their ancestors. Fixed by walking
  each assigned term's ancestor chain (nearest ancestor first) when none of
  the product's own categories has a rule — a direct assignment still wins
  over an inherited one, same "most specific wins" precedent as the existing
  product-override-beats-category rule. **Verified fixed**: restricting a
  parent category to US-only correctly 404'd a product assigned only to its
  child category, and the same product became visible again under an
  allowed-country preview.
- **Real (non-preview) visits always resolved to the store's own base
  country**, regardless of actual visitor location — proven with an A/B test
  switching a category between "US only" and "SN only" and observing a real
  US-located visit blocked in the first case and allowed in the second.
  Root cause: `WGC_Geolocation::get_visitor_country()` checked
  `WC()->customer->get_shipping_country()` before falling back to
  `WC_Geolocation::geolocate_ip()`. For a fresh visitor with no session,
  `get_shipping_country()` is seeded from WooCommerce's own "Default customer
  location" setting — on most stores, "Shop base address" — which returns the
  *shop's* country unconditionally, with no IP lookup involved at all. Fixed
  by dropping that shortcut and calling `geolocate_ip()` directly, matching
  what the plugin's own docs already promised. If geolocation is still wrong
  after this fix, see "Geolocation troubleshooting" below — the remaining
  causes (MaxMind license key, reverse-proxy IP forwarding) are WooCommerce/
  server-level, not this plugin. **Verified fixed**: confirmed real browsing
  IP resolved as United States (independently checked via ifconfig.co), then
  a Senegal-only category rule correctly 404'd a normal (no preview param)
  visit, and a US-only rule made it visible — no banner shown either time,
  confirming this was genuine geolocation and not the preview override.

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
- **Real-IP visibility behind a reverse proxy (Cloudflare or similar)**: this
  plugin makes no attempt to detect or correct for a proxy rewriting the
  visitor IP the origin server sees — it trusts whatever `WC_Geolocation`
  resolves. If the store is proxied and the origin isn't configured to read
  the proxy's forwarded-IP header (e.g. `CF-Connecting-IP` for Cloudflare),
  WooCommerce's own IP detection sees the proxy's IP, not the visitor's, and
  geolocation will be wrong regardless of this plugin. That's a server/
  WooCommerce-level fix (see "Geolocation troubleshooting" below), deliberately
  left out of this plugin's scope — silently rewriting sitewide IP detection
  from inside a catalog-visibility plugin is a bigger, security-relevant
  change (forwarded-IP headers are spoofable unless the origin firewall
  actually restricts direct access to the proxy's IP ranges) that the site
  owner should apply deliberately, not as a side effect of installing this.

## Geolocation troubleshooting

This plugin has no geolocation logic of its own — it calls
`WC_Geolocation::geolocate_ip()` directly and uses whatever country that
returns (see "Tested against a live install" below for why it calls this
directly rather than reading `WC()->customer`). If restriction rules aren't
matching real visitors correctly, the fault is almost always in what
WooCommerce itself resolves, not in this plugin:

- **MaxMind license key**: since WooCommerce 3.9, `WC_Geolocation` needs a
  MaxMind license key (free tier is fine) configured under WooCommerce →
  Settings → Integration → MaxMind Geolocation for the GeoIP database to
  download and stay updated. Without it, geolocation accuracy degrades or
  fails outright.
- **Reverse proxy (Cloudflare, etc.)**: see the limitation above — the origin
  needs to see the real visitor IP, not the proxy's. For Cloudflare
  specifically, the usual fix is a small `woocommerce_geolocation_ip` filter
  snippet (via a site-specific/must-use plugin, not this one) that prefers
  `$_SERVER['HTTP_CF_CONNECTING_IP']` when present.
- To confirm which of the two is at fault on a given store, use this plugin's
  own `?wgc_preview_country=XX` admin preview to verify the *rule* is correct
  independent of geolocation, then check what a real, undetected-country
  visit actually resolves to.

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
