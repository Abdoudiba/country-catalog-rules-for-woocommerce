=== Geo Catalog for WooCommerce ===
Contributors: (tbd)
Tags: woocommerce, geolocation, country restriction, catalog visibility, multi-country
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 9.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restrict product and category visibility by customer country, reusing WooCommerce's own geolocation instead of a second IP lookup.

== Description ==

Most country-restriction plugins for WooCommerce bring their own IP
geolocation. If your store already has WooCommerce's built-in geolocation
active (the MaxMind GeoIP2 database WooCommerce ships with), a second,
independent lookup can disagree with it near a border or behind a VPN.

Geo Catalog reuses `WC_Geolocation` — the same detection WooCommerce already
uses for shipping, tax, and currency — so there's only ever one source of
truth about where a visitor is.

= Features =

* Restrict a whole product category to specific countries in one place.
* Per-product override for exceptions to a category rule.
* Two modes: hide completely, or show but block purchase with a custom message.
* Admin preview mode (`?wgc_preview_country=XX`) — test rules without a VPN.
* Fails open on undetected location: never accidentally hides a product from
  a real customer because geolocation couldn't determine their country.

== Installation ==

1. Upload to `/wp-content/plugins/` or install via Plugins → Add New → Upload.
2. Activate.
3. Set category rules under Products → Categories.
4. Set per-product overrides under each product's Product Data → Geo Catalog tab.
5. Optional: customize the unavailable-product message under WooCommerce →
   Settings → Geo Catalog.

== Frequently Asked Questions ==

= Does this replace WooCommerce's own geolocation setting? =

No — it reads from it. Make sure WooCommerce's default customer location is
set to "Geolocate" (WooCommerce → Settings → General) for this plugin to
have a country to check against.

= What happens if a visitor's country can't be detected? =

Restricted products stay visible. The plugin fails open, not closed.

= Does this affect product feeds (Meta, Google, TikTok)? =

It hooks into WooCommerce's standard visibility filter, which is the correct
integration point, but hasn't been verified against every third-party feed
plugin. See the project README for the full list of known v1 limitations.

== Changelog ==

= 0.1.0 =
* Initial release: category and product-level country restriction, two
  restriction modes, admin preview, WooCommerce Settings tab.
