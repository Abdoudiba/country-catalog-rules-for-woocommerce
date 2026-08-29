=== Country Catalog Rules for WooCommerce ===
Contributors: abdoudiba
Tags: woocommerce, geolocation, country restriction, catalog visibility, multi-country
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
WC requires at least: 8.0
WC tested up to: 10.0
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restrict product and category visibility by customer country, reusing WooCommerce's own geolocation instead of a second IP lookup.

== Description ==

Most country-restriction plugins for WooCommerce bring their own IP
geolocation. If your store already has WooCommerce's built-in geolocation
active (the MaxMind GeoIP2 database WooCommerce ships with), a second,
independent lookup can disagree with it near a border or behind a VPN.

Country Catalog Rules reuses `WC_Geolocation` — the same detection WooCommerce already
uses for shipping, tax, and currency — so there's only ever one source of
truth about where a visitor is.

= Features =

* Restrict a whole product category to specific countries in one place.
* Per-product override for exceptions to a category rule.
* Two modes: hide completely, or show everywhere — including shop/category
  grid cards — with an Amazon-style "not available in your country" badge
  and a custom message that can include the visitor's detected country name.
* Admin preview mode (`?ccrw_preview_country=XX`) — test rules without a VPN.
* Fails open on undetected location: never accidentally hides a product from
  a real customer because geolocation couldn't determine their country.

== Installation ==

1. Upload to `/wp-content/plugins/` or install via Plugins → Add New → Upload.
2. Activate.
3. Set category rules under Products → Categories.
4. Set per-product overrides under each product's Product Data → Country Catalog Rules tab.
5. Optional: customize the unavailable-product message under WooCommerce →
   Settings → Country Catalog Rules.

== Frequently Asked Questions ==

= Does this replace WooCommerce's own geolocation setting? =

No — it calls WooCommerce's own `WC_Geolocation::geolocate_ip()` directly.
Make sure a MaxMind license key is configured (WooCommerce → Settings →
Integration → MaxMind Geolocation) so that lookup can actually resolve a
country. If your store is behind Cloudflare or another reverse proxy, the
origin also needs to see the real visitor IP — see the project README's
"Geolocation troubleshooting" section.

= What happens if a visitor's country can't be detected? =

Restricted products stay visible. The plugin fails open, not closed.

= Does this affect product feeds (Meta, Google, TikTok)? =

It hooks into WooCommerce's standard visibility filter, which is the correct
integration point, but hasn't been verified against every third-party feed
plugin. See the project README for the full list of known v1 limitations.

== Changelog ==

= 0.3.0 =
* Rename: the plugin is now "Country Catalog Rules for WooCommerce"
  (was "Geo Catalog for WooCommerce"). Internal prefix, text domain,
  option/meta keys and the admin-preview query parameter changed from
  `wgc` to `ccrw`. First public release under the new name.

= 0.2.1 =
* Add: declare the `Requires Plugins: woocommerce` dependency header.
* Compat: tested up to WordPress 7.1 and WooCommerce 10.0.

= 0.2.0 =
* Add: "Show, mark unavailable" mode now shows a badge on shop/category/
  search grid cards too (previously only the single product page), with
  the grid's Add to Cart button swapped for a "Not available" notice —
  matches how Amazon flags region-blocked products in listings, not just
  on the product page.
* Add: the unavailable message (WooCommerce → Settings → Country Catalog Rules) now
  supports a `{country}` token, replaced with the visitor's detected
  country name.

= 0.1.1 =
* Fix: category rules now cascade to subcategories (a restriction on a
  parent category applies to products only assigned to a child category).
* Fix: real (non-preview) visitor geolocation no longer defaults to the
  store's own base country — now calls WC_Geolocation::geolocate_ip()
  directly instead of a shortcut that was seeded from the store's default
  customer location setting.

= 0.1.0 =
* Initial release: category and product-level country restriction, two
  restriction modes, admin preview, WooCommerce Settings tab.
