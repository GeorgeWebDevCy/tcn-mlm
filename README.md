# TCN Consumer Network MLM Plugin

Custom WordPress plugin that layers an MLM programme on top of WooCommerce membership purchases. It tracks sponsor relationships, manages automatic upgrades for Gold/Platinum/Black tiers, records commissions, and exposes member dashboards with earnings and genealogy views.

## Features
- WooCommerce driven membership enrolment with automatic level upgrades (Gold -> Platinum -> Black).
- Automatic GitHub update checks via plugin-update-checker.
- Referral capture via query string and checkout field, optional cookie storage.
- Binary-style direct recruit tracking with network-size rollups and promotion logic.
- Commission ledger with configurable direct/passive payouts and REST access.
- Front-end dashboard shortcode for logged-in members (earnings, breakdown, history).
- Genealogy tree shortcode rendered from REST data with responsive styling.
- Admin settings page to adjust currency, passive depth, level names, fees, and commissions.
- WooCommerce product meta box to map products to membership levels.
- Automatic seeding of baseline membership products when the plugin activates (Blue, Gold, Platinum, Black).
- WooCommerce “My Account” menu entries for MLM Dashboard and Genealogy that reuse the bundled shortcodes.
- REST endpoints that power the TCNApp mobile membership catalogue and upgrade flows.
- Polished member dashboard and admin UI with TCN-branded styling.

## Requirements
- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+

## Companion Plugin
- [GN Password Login API](https://github.com/GeorgeWebDevCy/gn-password-login-api) ships the password-based authentication endpoints used by the same membership sites that run TCN MLM. Install it alongside this plugin to give mobile apps and third-party portals a lightweight REST interface for authenticating into WordPress while the MLM layer manages memberships and commissions.

## Installation
1. Copy the plugin directory into `wp-content/plugins/tcn-mlm`.
2. Activate “TCN Consumer Network MLM” from the WordPress admin Plugins screen.
3. Visit **TCN MLM** in the admin menu to review defaults and adjust membership level payouts or passive depth.

## Configuration Workflow
1. **Review the seeded membership products** (Blue, Gold, Platinum, Black) that are created automatically on activation. Adjust pricing, descriptions, and catalog visibility as needed.
2. **Create additional membership products** (if required) and assign their level via the “TCN MLM Membership Level” field found under Product ▸ General.
3. **Capture sponsors** by sharing referral URLs (e.g. `https://example.com/shop?ref=123`) or by adding the optional checkout field `tcn_mlm_sponsor`.
4. **Publish shortcodes** where needed:
   - ` [tcn_member_dashboard] ` – Member earnings, counts, and recent commissions.
   - ` [tcn_genealogy] ` – Interactive downline tree for the logged-in user.
5. Ensure sponsors recruit at least two paid members to trigger automatic upgrades (Gold -> Platinum). Network size rollups continue promoting to Black.
6. Completed orders that include a mapped membership product now update the purchaser’s membership level automatically.

## WooCommerce Account Integration
- The plugin now injects **MLM Dashboard** and **MLM Genealogy** links into WooCommerce’s “My Account” navigation.
- Visiting those endpoints renders the same content as the ` [tcn_member_dashboard] ` and ` [tcn_genealogy] ` shortcodes, so you can support both standalone pages and native account tabs.
- Endpoints are registered on activation and exposed under `/my-account/tcn-member-dashboard/` and `/my-account/tcn-genealogy/` once permalinks flush.

## Order Automation
- When a WooCommerce order reaches the **Completed** status, the plugin inspects its line items and looks for products tied to a TCN membership level.
- The highest-ranking level found in the order becomes the member’s active level, and the change triggers the `tcn_mlm_membership_changed` action for custom integrations.
- Membership join dates are populated automatically the first time an order promotes a user.

## Seeded Membership Products
- On activation the plugin creates hidden WooCommerce products for the baseline levels (Blue, Gold, Platinum, Black) if they’re missing.
- Each seeded product is automatically tagged with the correct `TCN MLM Membership Level`, so orders placed against them immediately upgrade members.
- Feel free to adjust pricing, content, or visibility of the generated products—or replace them with your own items using the same membership field.
- Products are automatically assigned to the `Memberships` WooCommerce category (created on activation if missing) so catalog filtering stays consistent.
- Seeded products are also removed from the default `Uncategorized` bucket so only relevant categories remain visible.
- Pricing and category assignments are re-synced on every load to keep existing products aligned with these defaults.
- A scheduled background sync keeps product pricing and categories enforced even if data is edited manually.

## Mobile App Integration
- The [TCNApp](https://github.com/GeorgeWebDevCy/TCNApp) React Native client consumes the GN Password Login API endpoints for authentication and the `gn/v1/memberships/*` endpoints defined in this plugin for membership upgrades.
- Exposing membership details: `wp-json/wp/v2/users/me` now includes `membership_tier`, `membership_expiry`, and `membership_benefits` metadata so the app can hydrate the dashboard without extra calls.
- Membership plan catalogue: `GET /wp-json/gn/v1/memberships/plans` returns the plan list consumed by the app’s paywall UI. Adjust `tcn_mlm_levels` in WordPress to change pricing, copy, or benefits.
- Prices are exposed in minor units (e.g. THB satang) to match the mobile app’s expectations; the WooCommerce products remain priced in major units.
- Upgrade flows: `POST /wp-json/gn/v1/memberships/confirm` can be triggered by authenticated mobile clients to promote a user after an out-of-band payment confirmation. Stripe intent creation (`/stripe-intent`) returns a 501 response until real payment handling is configured—hook into `tcn_mlm_membership_create_payment_session` to provide your own payload.
- Customise the REST responses by filtering `tcn_mlm_membership_create_payment_session` and `tcn_mlm_membership_confirm_response` to integrate with billing providers or extend the return payload.
- The admin settings screen now previews membership tiers with TCN branding so site managers can keep pricing aligned with the mobile experience.
- Ensure the GN Password Login API plugin is active so `/wp-json/gn/v1/login` and related endpoints remain available to the mobile client.

## Shortcodes
| Shortcode | Description |
|-----------|-------------|
| ` [tcn_member_dashboard] ` | Displays membership status, direct/network metrics, commission totals, and recent ledger entries. |
| ` [tcn_genealogy] ` | Outputs a REST-powered genealogy tree with expandable recruits. |
| ` [tcn_mlm_optin] ` | Basic opt-in container that can be extended or replaced with custom forms. |

## REST Endpoints
- `GET /wp-json/tcn-mlm/v1/genealogy?depth=4`
  - Requires authentication. Returns the authenticated member’s downline tree plus summary metrics. Depth defaults to 4 levels and is capped at 6.

## Data Notes
- User meta keys used: `_tcn_membership_level`, `_tcn_sponsor_id`, `_tcn_network_owner`, `_tcn_direct_recruits`, `_tcn_network_size`, `_tcn_joined_at`.
- Commission entries are stored in the custom table `{wpdb_prefix}tcn_mlm_commissions`.
- Plugin options: `tcn_mlm_general` (global settings) and `tcn_mlm_levels` (per-level configuration).

## GitHub Updates
- The plugin bundles [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) and is configured to pull releases from [GeorgeWebDevCy/tcn-mlm](https://github.com/GeorgeWebDevCy/tcn-mlm), mirroring the update bootstrap used in [GN Password Login API](https://github.com/GeorgeWebDevCy/gn-password-login-api).
- For private repositories, define `TCN_MLM_GITHUB_TOKEN` (or filter `tcn_mlm_github_token`) to supply a GitHub token before updates run.

## Development
- See `docs/architecture.md` for subsystem breakdown and data flow diagrams.
- Hooks provided:
  - `tcn_mlm_membership_changed( $user_id, $level, $context )` fires on level changes.
- When modifying queries or table schema, bump `Activator::DB_VERSION` and rerun activation steps.
