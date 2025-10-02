<?php

namespace TCN\MLM\WooCommerce;

use TCN\MLM\Contracts\Bootable;
use TCN\MLM\Plugin;

class AccountEndpoints implements Bootable {
    public const ENDPOINT_MEMBER_DASHBOARD = 'tcn-member-dashboard';
    public const ENDPOINT_GENEALOGY = 'tcn-genealogy';

    public const ENDPOINTS = [
        self::ENDPOINT_MEMBER_DASHBOARD => [
            'shortcode' => 'tcn_member_dashboard',
        ],
        self::ENDPOINT_GENEALOGY => [
            'shortcode' => 'tcn_genealogy',
        ],
    ];

    private Plugin $plugin;

    private array $endpoints;

    public function __construct( Plugin $plugin ) {
        $this->plugin = $plugin;
        $this->endpoints = self::ENDPOINTS;
    }

    public function boot(): void {
        add_action( 'init', [ $this, 'registerEndpoints' ], 9 );
        add_filter( 'woocommerce_get_query_vars', [ $this, 'addQueryVars' ] );
        add_filter( 'woocommerce_account_menu_items', [ $this, 'injectMenuItems' ] );

        foreach ( $this->endpoints as $slug => $config ) {
            add_action( 'woocommerce_account_' . $slug . '_endpoint', function () use ( $config ) {
                echo do_shortcode( '[' . $config['shortcode'] . ']' );
            } );
        }
    }

    public function registerEndpoints(): void {
        if ( function_exists( 'tcn_mlm_register_account_endpoints' ) ) {
            tcn_mlm_register_account_endpoints();
            return;
        }

        foreach ( array_keys( $this->endpoints ) as $slug ) {
            add_rewrite_endpoint( $slug, EP_ROOT | EP_PAGES );
        }
    }

    public function addQueryVars( array $vars ): array {
        foreach ( array_keys( $this->endpoints ) as $slug ) {
            $vars[ $slug ] = $slug;
        }

        return $vars;
    }

    public function injectMenuItems( array $items ): array {
        $new_items = [];

        foreach ( $this->endpoints as $slug => $config ) {
            $new_items[ $slug ] = $this->getMenuLabel( $slug );
        }

        if ( isset( $items['dashboard'] ) ) {
            $start = [ 'dashboard' => $items['dashboard'] ];
            unset( $items['dashboard'] );

            return $start + $new_items + $items;
        }

        return $items + $new_items;
    }

    private function getMenuLabel( string $slug ): string {
        switch ( $slug ) {
            case self::ENDPOINT_MEMBER_DASHBOARD:
                return esc_html__( 'MLM Dashboard', 'tcn-mlm' );
            case self::ENDPOINT_GENEALOGY:
                return esc_html__( 'MLM Genealogy', 'tcn-mlm' );
            default:
                return esc_html( ucfirst( str_replace( '-', ' ', $slug ) ) );
        }
    }
}
