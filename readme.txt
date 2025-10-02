=== TCN MLM ===
Contributors: georgewebdevcy
Tags: woocommerce, mlm, memberships, commissions, genealogy
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
TCN MLM layers a multi-level marketing engine on top of WooCommerce memberships. It keeps sponsor relationships in sync, applies tier upgrade rules, and tracks commissions that downline members generate. Dashboards and REST endpoints expose the data needed for your team to manage their networks in real time, and the same views appear as native WooCommerce “My Account” tabs for members. The plugin seeds baseline membership products with the correct mapping so completed orders automatically promote customers.

This initial bootstrap wires the plugin into WordPress, preps the update manager, and lays the groundwork for the service container described in the architecture guide. Update delivery is handled through the excellent [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) library.

== Installation ==
1. Upload the `tcn-mlm` directory to `/wp-content/plugins/` or install the plugin via the WordPress admin Plugins screen.
2. Activate **TCN MLM** through the Plugins screen.
3. The Plugin Update Checker library ships with the plugin (see the `plugin-update-checker/` directory). If you prefer to manage dependencies with Composer, replace that folder with your own autoload setup.
4. If you use a GitHub branch other than `main`, add a filter to `tcn_mlm_update_repository_branch` (or define the `TCN_MLM_UPDATE_REPOSITORY_BRANCH` constant) so the updater tracks the correct branch.
5. Visit the TCN MLM settings page (coming soon) to complete onboarding.

== Mobile App Integration ==
- Pair this plugin with the [TCNApp](https://github.com/GeorgeWebDevCy/TCNApp) React Native client for mobile access. The app authenticates through GN Password Login API endpoints and reads membership data from this plugin.
- `GET /wp-json/gn/v1/memberships/plans` exposes the catalogue consumed by the app’s upgrade screen. Adjust `tcn_mlm_levels` to change pricing, copy, or benefits.
- `POST /wp-json/gn/v1/memberships/confirm` lets authenticated users promote themselves after an out-of-band payment is confirmed.
- `POST /wp-json/gn/v1/memberships/stripe-intent` currently returns a 501 response until Stripe payment handling is configured on your site (hook into `tcn_mlm_membership_create_payment_session` to supply your own payload).
- The WordPress profile endpoint (`/wp-json/wp/v2/users/me`) now includes `membership_tier`, `membership_expiry`, and `membership_benefits` metadata so the app can hydrate dashboards without extra calls.
- Developers can customise the upgrade payloads with the `tcn_mlm_membership_confirm_response` filter.
- Seeded products automatically join the `Memberships` WooCommerce category for consistent storefront organisation.
- The front-end dashboard and admin screens follow the TCN app branding for a cohesive experience.

== Frequently Asked Questions ==
= How do automated updates work? =
The bootstrap file loads the bundled Plugin Update Checker library and points it to this repository on GitHub. When you tag a new release, WordPress will see the update and offer it to every site running this plugin. Keep the `plugin-update-checker/` directory in place (or provide your own autoloader) and confirm that the repository URL (`https://github.com/GeorgeWebDevCy/tcn-mlm`) matches your hosting setup.

= Can I change the repository URL without editing core files? =
Yes. Hook into `tcn_mlm_update_repository_url` and return the URL for your public repository, or define the `TCN_MLM_UPDATE_REPOSITORY` constant in `wp-config.php` before the plugin loads.

= Do I need to create separate pages for the WooCommerce account tabs? =
No. The plugin automatically wires **MLM Dashboard** and **MLM Genealogy** links into the WooCommerce “My Account” menu. Those endpoints render the same markup as the shortcodes, so you can keep both options or disable the standalone pages if you prefer.

= Does the plugin create membership products for me? =
Yes. On activation the plugin creates hidden WooCommerce products for the default levels (Blue, Gold, Platinum, Black) if they’re missing. You can customise pricing or replace them with your own items.

= How do I map a WooCommerce product to a membership level? =
Edit the product in the WordPress admin and locate the **TCN MLM Membership Level** dropdown inside the Product ▸ General panel. Pick the level the product should grant. When a customer completes an order that includes that product, their account is promoted to the selected level automatically.

= What's next for the plugin? =
Future commits will introduce the service container, WooCommerce membership sync, commission calculations, REST API endpoints, and dashboards described in `architecture.md`.

== Changelog ==
= 0.2.0 =
* Force membership pricing to 0 / 500 / 1,200 / 2,000 THB regardless of historical settings and re-run synchronisation every page load plus hourly via WP-Cron.
* Keep auto-generated products in the `Memberships` category only, stripping `Uncategorized` when necessary.

= 0.1.9 =
* Enforce correct membership pricing and category assignment on every load so existing products stay in sync.
* Remove seeded products from `Uncategorized` after adding them to the `Memberships` category.

= 0.1.8 =
* Correct seeded membership pricing to 0 / 500 / 1200 / 2000 THB and ensure updates apply to existing products.
* Automatically remove seeded products from the `Uncategorized` group after placing them in the `Memberships` category.

= 0.1.7 =
* Refresh the member dashboard and admin settings page with TCN branding and responsive layouts.
* Automatically assign seeded membership products to the `Memberships` WooCommerce product category (creating it if needed).
* Enqueue shared styling for both front-end shortcodes and the admin experience.

= 0.1.6 =
* Bump plugin version to ship the mobile-app integration endpoints and REST user metadata additions.

= 0.1.5 =
* Seed baseline membership products on activation and ensure they carry the correct TCN MLM membership level meta.
* Avoid duplicate product creation by reusing existing items matched by level or title.
* Expose `/wp-json/gn/v1/memberships/*` endpoints and enrich the user REST profile so the mobile app can read membership plans and statuses.

= 0.1.4 =
* Add WooCommerce product data field for selecting the membership level a SKU grants.
* Promote customers automatically when orders complete, emitting the `tcn_mlm_membership_changed` action.

= 0.1.3 =
* Add WooCommerce My Account endpoints for the MLM dashboard and genealogy, mirroring the bundled shortcodes.
* Flush rewrite rules on activation so endpoints become immediately available.

= 0.1.2 =
* Add service container bootstrap, autoloader, and default settings seeding.
* Register membership/user meta, commission table schema, REST metrics endpoint, admin page, and placeholder shortcodes.

= 0.1.1 =
* Bump plugin version to verify automatic updater flow.

= 0.1.0 =
* Add the base plugin bootstrap with activation guards, localization loading, and GitHub-powered updates via plugin-update-checker.
* Provide this WordPress readme to surface plugin metadata and setup notes inside the admin.

== Upgrade Notice ==
= 0.2.0 =
Stronger pricing + category enforcement and scheduled resyncs for seeded membership products.

= 0.1.9 =
Ongoing pricing/category sync for auto-generated membership products. Update if you rely on the seeded catalogue.

= 0.1.8 =
Pricing and categorisation fixes for seeded membership products. Update if you rely on the auto-generated products.

= 0.1.7 =
Brand-aligned UI updates and automatic category assignment for seeded products. Update if you want the polished member dashboard or WooCommerce categorisation.

= 0.1.6 =
Version bump to deliver the new mobile endpoints, REST metadata, and refreshed branding. Update now if you rely on the TCN app.

= 0.1.5 =
Activation now seeds default membership products and sets their TCN levels automatically. Review the generated products and adjust pricing before launch.

= 0.1.4 =
Map your membership products with the new WooCommerce field so completed orders seamlessly update customer levels.

= 0.1.3 =
Adds WooCommerce “My Account” menu entries that surface the MLM dashboard and genealogy views alongside the existing shortcodes.

= 0.1.2 =
Introduces the plugin service container plus initial membership, commission, REST, admin, and shortcode scaffolding.

= 0.1.1 =
Version bump to trigger and validate the GitHub-based auto-updater.

= 0.1.0 =
Initial public skeleton release that prepares the plugin for upcoming features and enables GitHub-based automatic updates.
