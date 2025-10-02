=== TCN MLM ===
Contributors: georgewebdevcy
Tags: woocommerce, mlm, memberships, commissions, genealogy
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
TCN MLM layers a multi-level marketing engine on top of WooCommerce memberships. It keeps sponsor relationships in sync, applies tier upgrade rules, and tracks commissions that downline members generate. Dashboards and REST endpoints expose the data needed for your team to manage their networks in real time.

This initial bootstrap wires the plugin into WordPress, preps the update manager, and lays the groundwork for the service container described in the architecture guide. Update delivery is handled through the excellent [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) library.

== Installation ==
1. Upload the `tcn-mlm` directory to `/wp-content/plugins/` or install the plugin via the WordPress admin Plugins screen.
2. Activate **TCN MLM** through the Plugins screen.
3. The Plugin Update Checker library ships with the plugin (see the `plugin-update-checker/` directory). If you prefer to manage dependencies with Composer, replace that folder with your own autoload setup.
4. If you use a GitHub branch other than `main`, add a filter to `tcn_mlm_update_repository_branch` (or define the `TCN_MLM_UPDATE_REPOSITORY_BRANCH` constant) so the updater tracks the correct branch.
5. Visit the TCN MLM settings page (coming soon) to complete onboarding.

== Frequently Asked Questions ==
= How do automated updates work? =
The bootstrap file loads the bundled Plugin Update Checker library and points it to this repository on GitHub. When you tag a new release, WordPress will see the update and offer it to every site running this plugin. Keep the `plugin-update-checker/` directory in place (or provide your own autoloader) and confirm that the repository URL (`https://github.com/GeorgeWebDevCy/tcn-mlm`) matches your hosting setup.

= Can I change the repository URL without editing core files? =
Yes. Hook into `tcn_mlm_update_repository_url` and return the URL for your public repository, or define the `TCN_MLM_UPDATE_REPOSITORY` constant in `wp-config.php` before the plugin loads.

= What's next for the plugin? =
Future commits will introduce the service container, WooCommerce membership sync, commission calculations, REST API endpoints, and dashboards described in `architecture.md`.

== Changelog ==
= 0.1.1 =
* Bump plugin version to verify automatic updater flow.

= 0.1.0 =
* Add the base plugin bootstrap with activation guards, localization loading, and GitHub-powered updates via plugin-update-checker.
* Provide this WordPress readme to surface plugin metadata and setup notes inside the admin.

== Upgrade Notice ==
= 0.1.1 =
Version bump to trigger and validate the GitHub-based auto-updater.

= 0.1.0 =
Initial public skeleton release that prepares the plugin for upcoming features and enables GitHub-based automatic updates.
