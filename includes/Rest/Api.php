<?php

namespace TCN\MLM\Rest;

use TCN\MLM\Contracts\Bootable;
use TCN\MLM\Plugin;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

class Api implements Bootable {
    private Plugin $plugin;

    public function __construct( Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    public function boot(): void {
        add_action( 'rest_api_init', [ $this, 'registerRoutes' ] );
    }

    public function registerRoutes(): void {
        register_rest_route( 'tcn-mlm/v1', '/metrics', [
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => [ $this, 'authorizeMember' ],
            'callback'            => [ $this, 'handleMetrics' ],
        ] );
    }

    public function authorizeMember(): bool|WP_Error {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'tcn_mlm_rest_unauthorized', __( 'Authentication required.', 'tcn-mlm' ), [ 'status' => 401 ] );
        }

        return true;
    }

    public function handleMetrics( WP_REST_Request $request ) {
        $user_id = get_current_user_id();

        return [
            'membership_level' => get_user_meta( $user_id, '_tcn_membership_level', true ) ?: 'blue',
            'direct_recruits'  => (int) get_user_meta( $user_id, '_tcn_direct_recruits', true ),
            'network_size'     => (int) get_user_meta( $user_id, '_tcn_network_size', true ),
        ];
    }
}
