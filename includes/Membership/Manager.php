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
}
