# TCN MLM Plugin Architecture

## Overview
The plugin adds a network marketing layer on top of WooCommerce memberships. It tracks sponsor relationships, handles automatic upgrades, records commissions, and exposes dashboards for members to monitor their earnings and downlines.

## Key Components
- **Plugin Bootstrap (`tcn-mlm.php`)** - Registers activation hooks, loads dependencies, and bootstraps main service container.
- **Service Container (`TCN\\MLM\\Plugin`)** - Coordinates setup of subsystems (membership, network, commissions, dashboards, REST routes).
- **Membership Manager** - Stores membership levels on users, enforces upgrade rules (Gold -> Platinum after 2 direct recruits, Platinum -> Black after 2 active network members), and syncs with WooCommerce order status.
- **Network Service** - Maintains sponsor relationships, assigns network owners, and exposes traversal helpers for genealogies and commission roll-ups.
- **Commission Manager** - Calculates commissions during WooCommerce order completion, records them in a custom table, and provides summary totals for dashboards.
- **Admin UI** - Provides settings for membership tiers, WooCommerce product mapping, and manual adjustments. Adds product meta boxes for associating membership level and annual fee.
- **Member Dashboard Shortcodes** - Front-end shortcodes for members to view earnings, commissions, and downline activity. Genealogy output uses localized REST endpoints to render an interactive tree.
- **REST API** - Namespaced endpoints under `/wp-json/tcn-mlm/v1/` exposing genealogy data, member metrics, and commission summaries for authenticated users.
- **Update Manager** - Wraps plugin-update-checker to fetch releases from GitHub and installs updates automatically.

## Data Model
- **User Meta**
  - `_tcn_membership_level` (string: `blue|gold|platinum|black`)
  - `_tcn_sponsor_id` (int, user ID of direct sponsor)
  - `_tcn_network_owner` (int, root of the member's current network)
  - `_tcn_direct_recruits` (int, cached count for upgrade checks)
- **Options**
  - `tcn_mlm_levels` - Associative array defining membership metadata (fee, commission amounts, upgrade thresholds).
  - `tcn_mlm_general` - Global settings (default sponsor, whether Blue users can see dashboards, etc.).
- **Custom Table** `${wpdb->prefix}tcn_mlm_commissions`
  - `id` bigint PK
  - `sponsor_id` bigint FK -> `wp_users.ID`
  - `member_id` bigint (recruit the commission came from)
  - `order_id` bigint WooCommerce order reference
  - `level` varchar (e.g., `direct`, `passive`)
  - `amount` decimal(10,2)
  - `currency` char(3)
  - `status` varchar (`pending`, `paid`, `cancelled`)
  - `created_at` datetime, `updated_at` datetime

## WooCommerce Integration Flow
1. Member purchases a membership product (configured via product meta).
2. On `woocommerce_order_status_completed`, the Membership Manager promotes the purchasing user to the corresponding membership level and links the sponsor using referral metadata (shortcode or query param).
3. Network Service updates direct recruit counts and determines whether the sponsor should form their own network or upgrade to Platinum/Black.
4. Commission Manager records a direct commission for the sponsor. It then walks up the upline hierarchy to award passive commissions per rules (currently 1 level for Gold/Platinum to align with provided scenarios).
5. Dashboards and REST endpoints read aggregated data from the commission table and user meta for reporting.

## Genealogy Visualization
- REST endpoint returns a nested tree structure limited to the authenticated user's downline.
- Front-end script renders the tree using nested HTML lists styled with CSS connectors. Future iterations can swap in D3 or another visualization library.

## Extensibility
- Hooks (`do_action` / `apply_filters`) are provided around commission calculations, upgrade thresholds, and REST responses for future tier additions.
- Additional membership tiers can be added via settings without schema changes.
