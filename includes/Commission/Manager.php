<?php

namespace TCN\MLM\Commission;

use TCN\MLM\Contracts\Bootable;
use TCN\MLM\Plugin;
use TCN\MLM\Support\Environment;

class Manager implements Bootable {
    private Plugin $plugin;
    private Environment $environment;
    private string $schemaVersion = '1.0.0';

    public function __construct( Plugin $plugin ) {
        $this->plugin      = $plugin;
        $this->environment = $this->plugin->has( Environment::class )
            ? $this->plugin->get( Environment::class )
            : new Environment( $plugin );
    }

    public function boot(): void {
        add_action( 'init', [ $this, 'ensureSchema' ] );
    }

    public function ensureSchema(): void {
        $stored_version = get_option( 'tcn_mlm_db_version', '0.0.0' );

        if ( version_compare( $stored_version, $this->schemaVersion, '>=' ) ) {
            return;
        }

        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        global $wpdb;

        $table = $wpdb->prefix . 'tcn_mlm_commissions';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            sponsor_id bigint(20) unsigned NOT NULL,
            member_id bigint(20) unsigned NOT NULL,
            order_id bigint(20) unsigned NOT NULL,
            level varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL DEFAULT 0,
            currency char(3) NOT NULL DEFAULT 'USD',
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY sponsor_id (sponsor_id),
            KEY member_id (member_id),
            KEY order_id (order_id)
        ) {$charset_collate};";

        dbDelta( $sql );

        update_option( 'tcn_mlm_db_version', $this->schemaVersion );
    }
}
