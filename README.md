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

## Requirements
- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+

## Installation
1. Copy the plugin directory into `wp-content/plugins/tcn-mlm`.
2. Activate “TCN Consumer Network MLM” from the WordPress admin Plugins screen.
3. Visit **TCN MLM** in the admin menu to review defaults and adjust membership level payouts or passive depth.

## Configuration Workflow
1. **Create membership products** in WooCommerce and assign their level via the “TCN Membership Level” select box.
2. **Capture sponsors** by sharing referral URLs (e.g. `https://example.com/shop?ref=123`) or by adding the optional checkout field `tcn_mlm_sponsor`.
3. **Publish shortcodes** where needed:
   - ` [tcn_member_dashboard] ` – Member earnings, counts, and recent commissions.
   - ` [tcn_genealogy] ` – Interactive downline tree for the logged-in user.
4. Ensure sponsors recruit at least two paid members to trigger automatic upgrades (Gold -> Platinum). Network size rollups continue promoting to Black.

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
- The plugin bundles [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) and is configured to pull releases from [GeorgeWebDevCy/tcn-mlm](https://github.com/GeorgeWebDevCy/tcn-mlm).
- For private repositories, define `TCN_MLM_GITHUB_TOKEN` (or filter `tcn_mlm_github_token`) to supply a GitHub token before updates run.

## Development
- See `docs/architecture.md` for subsystem breakdown and data flow diagrams.
- Hooks provided:
  - `tcn_mlm_membership_changed( $user_id, $level, $context )` fires on level changes.
- When modifying queries or table schema, bump `Activator::DB_VERSION` and rerun activation steps.






