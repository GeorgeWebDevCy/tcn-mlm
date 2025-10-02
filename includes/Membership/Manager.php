<?php

namespace TCN\MLM\Membership;

use TCN\MLM\Contracts\Bootable;
use TCN\MLM\Plugin;

class Manager implements Bootable {
    private Plugin $plugin;

    public function __construct( Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    public function boot(): void {
        add_action( 'init', [ $this, 'registerUserMeta' ] );
        add_action( 'user_register', [ $this, 'assignDefaultMembership' ], 25 );
        add_action( 'woocommerce_order_status_completed', [ $this, 'handleOrderCompleted' ], 20 );
    }

    public function registerUserMeta(): void {
        register_meta( 'user', '_tcn_membership_level', [
            'type'              => 'string',
            'description'       => 'TCN MLM membership level',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function () {
                return current_user_can( 'list_users' );
            },
        ] );

        register_meta( 'user', '_tcn_sponsor_id', [
            'type'              => 'integer',
            'description'       => 'TCN MLM sponsor ID',
            'single'            => true,
            'show_in_rest'      => false,
            'sanitize_callback' => 'absint',
            'auth_callback'     => '__return_false',
        ] );

        register_meta( 'user', '_tcn_network_owner', [
            'type'              => 'integer',
            'description'       => 'Root user ID for the network',
            'single'            => true,
            'show_in_rest'      => false,
            'sanitize_callback' => 'absint',
            'auth_callback'     => '__return_false',
        ] );

        register_meta( 'user', '_tcn_direct_recruits', [
            'type'              => 'integer',
            'description'       => 'Cached direct recruit count',
            'single'            => true,
            'show_in_rest'      => false,
            'sanitize_callback' => 'absint',
            'auth_callback'     => '__return_false',
        ] );

        register_meta( 'user', '_tcn_network_size', [
            'type'              => 'integer',
            'description'       => 'Cached network size',
            'single'            => true,
            'show_in_rest'      => false,
            'sanitize_callback' => 'absint',
            'auth_callback'     => '__return_false',
        ] );

        register_meta( 'user', '_tcn_joined_at', [
            'type'              => 'string',
            'description'       => 'Date when member joined network',
            'single'            => true,
            'show_in_rest'      => false,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => '__return_false',
        ] );
    }

    public function assignDefaultMembership( int $user_id ): void {
        $level = get_user_meta( $user_id, '_tcn_membership_level', true );

        if ( ! $level ) {
            update_user_meta( $user_id, '_tcn_membership_level', $this->getDefaultLevel() );
        }
    }

    private function getDefaultLevel(): string {
        $levels = get_option( 'tcn_mlm_levels', [] );

        if ( isset( $levels['default'] ) && is_string( $levels['default'] ) ) {
            return sanitize_key( $levels['default'] );
        }

        return 'blue';
    }

    public function handleOrderCompleted( int $order_id ): void {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $user_id = $order->get_user_id();

        if ( ! $user_id ) {
            return;
        }

        $new_level = $this->determineLevelFromOrder( $order );

        if ( ! $new_level ) {
            return;
        }

        $current_level = get_user_meta( $user_id, '_tcn_membership_level', true );

        if ( $current_level === $new_level ) {
            return;
        }

        update_user_meta( $user_id, '_tcn_membership_level', $new_level );

        if ( ! get_user_meta( $user_id, '_tcn_joined_at', true ) ) {
            update_user_meta( $user_id, '_tcn_joined_at', current_time( 'mysql' ) );
        }

        do_action( 'tcn_mlm_membership_changed', $user_id, $new_level, 'order_completed' );
    }

    private function determineLevelFromOrder( $order ): ?string {
        $candidate = null;
        $candidate_priority = -1;
        $priorities = $this->getLevelPriorities();

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();

            if ( ! $product ) {
                continue;
            }

            $level = sanitize_key( (string) $product->get_meta( '_tcn_membership_level', true ) );

            if ( '' === $level ) {
                continue;
            }

            if ( ! isset( $priorities[ $level ] ) ) {
                continue;
            }

            $priority = $priorities[ $level ];

            if ( $priority > $candidate_priority ) {
                $candidate          = $level;
                $candidate_priority = $priority;
            }
        }

        return $candidate;
    }

    private function getLevelPriorities(): array {
        $levels_option = get_option( 'tcn_mlm_levels', [] );

        if ( isset( $levels_option['levels'] ) && is_array( $levels_option['levels'] ) && $levels_option['levels'] ) {
            $levels = array_keys( $levels_option['levels'] );
        } else {
            $levels = [ 'blue', 'gold', 'platinum', 'black' ];
        }

        $priorities = [];

        foreach ( array_values( $levels ) as $index => $level ) {
            $priorities[ sanitize_key( $level ) ] = $index;
        }

        return $priorities;
    }
}
