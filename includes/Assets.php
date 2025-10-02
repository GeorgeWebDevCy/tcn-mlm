<?php

namespace TCN\MLM;

class Assets {
    public static function register_frontend(): void {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin' ] );
    }

    public static function enqueue_frontend(): void {
        wp_register_style(
            'tcn-mlm-frontend',
            TCN_MLM_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            TCN_MLM_VERSION
        );
    }

    public static function enqueue_admin( string $hook_suffix ): void {
        if ( 'toplevel_page_tcn-mlm' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style( 'tcn-mlm-frontend', TCN_MLM_PLUGIN_URL . 'assets/css/frontend.css', [], TCN_MLM_VERSION );
    }
}
