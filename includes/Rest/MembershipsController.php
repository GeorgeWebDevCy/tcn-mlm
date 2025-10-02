<?php

namespace TCN\MLM\Rest;

use TCN\MLM\Contracts\Bootable;
use TCN\MLM\Plugin;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

class MembershipsController implements Bootable {
    private Plugin $plugin;

    public function __construct( Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    public function boot(): void {
        add_action( 'rest_api_init', [ $this, 'registerRoutes' ] );
    }

    public function registerRoutes(): void {
        register_rest_route(
            'gn/v1',
            '/memberships/plans',
            [
                'methods'             => WP_REST_Server::READABLE,
                'permission_callback' => '__return_true',
                'callback'            => [ $this, 'getMembershipPlans' ],
            ]
        );

        register_rest_route(
            'gn/v1',
            '/memberships/stripe-intent',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'permission_callback' => [ $this, 'requireAuthentication' ],
                'callback'            => [ $this, 'createStripeIntent' ],
            ]
        );

        register_rest_route(
            'gn/v1',
            '/memberships/confirm',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'permission_callback' => [ $this, 'requireAuthentication' ],
                'callback'            => [ $this, 'confirmMembershipUpgrade' ],
            ]
        );
    }

    public function requireAuthentication(): bool {
        return is_user_logged_in();
    }

    public function getMembershipPlans(): array {
        $levels = tcn_mlm_get_levels();
        $plans  = [];

        foreach ( $levels as $slug => $config ) {
            $plans[] = [
                'id'          => 'tcn-mlm-' . $slug,
                'name'        => (string) ( $config['label'] ?? ucfirst( $slug ) ),
                'price'       => isset( $config['fee'] ) ? (float) $config['fee'] : 0.0,
                'currency'    => strtoupper( (string) ( $config['currency'] ?? 'THB' ) ),
                'interval'    => (string) ( $config['interval'] ?? 'year' ),
                'description' => (string) ( $config['description'] ?? '' ),
                'features'    => array_values( is_array( $config['features'] ?? null ) ? $config['features'] : [] ),
                'highlight'   => ! empty( $config['highlight'] ),
                'metadata'    => [
                    'level' => $slug,
                ],
            ];
        }

        return [
            'plans' => $plans,
        ];
    }

    public function createStripeIntent( WP_REST_Request $request ) {
        $plan_id = (string) $request->get_param( 'planId' );

        if ( '' === trim( $plan_id ) ) {
            return new WP_Error(
                'tcn_mlm_missing_plan',
                __( 'A membership plan is required.', 'tcn-mlm' ),
                [ 'status' => 400 ]
            );
        }

        $level  = preg_replace( '/^tcn-mlm-/', '', $plan_id );
        $config = tcn_mlm_get_level_config( $level );

        $filtered = apply_filters(
            'tcn_mlm_membership_create_payment_session',
            null,
            [
                'plan_id' => $plan_id,
                'level'   => $level,
                'config'  => $config,
                'request' => $request,
                'user_id' => get_current_user_id(),
            ]
        );

        if ( $filtered instanceof WP_Error ) {
            return $filtered;
        }

        if ( is_array( $filtered ) ) {
            return $filtered;
        }

        return new WP_Error(
            'tcn_mlm_stripe_not_configured',
            __( 'Stripe membership payments are not configured for this site yet.', 'tcn-mlm' ),
            [ 'status' => 501 ]
        );
    }

    public function confirmMembershipUpgrade( WP_REST_Request $request ) {
        $plan_id = (string) $request->get_param( 'planId' );

        if ( '' === trim( $plan_id ) ) {
            return new WP_Error(
                'tcn_mlm_missing_plan',
                __( 'A membership plan is required.', 'tcn-mlm' ),
                [ 'status' => 400 ]
            );
        }

        $level = preg_replace( '/^tcn-mlm-/', '', $plan_id );
        $config = tcn_mlm_get_level_config( $level );

        if ( empty( $config ) ) {
            return new WP_Error(
                'tcn_mlm_invalid_plan',
                __( 'The selected membership plan is not available.', 'tcn-mlm' ),
                [ 'status' => 404 ]
            );
        }

        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return new WP_Error(
                'tcn_mlm_not_authenticated',
                __( 'You must be logged in to upgrade your membership.', 'tcn-mlm' ),
                [ 'status' => 401 ]
            );
        }

        update_user_meta( $user_id, '_tcn_membership_level', $level );
        do_action( 'tcn_mlm_membership_changed', $user_id, $level, 'mobile_confirm' );

        $response = [
            'success'    => true,
            'message'    => __( 'Membership updated successfully.', 'tcn-mlm' ),
            'membership' => [
                'tier'      => $level,
                'expiresAt' => null,
                'benefits'  => isset( $config['benefits'] ) && is_array( $config['benefits'] ) ? array_values( $config['benefits'] ) : [],
            ],
        ];

        /**
         * Filter the REST response returned after a mobile membership upgrade.
         *
         * @param array             $response Default response payload.
         * @param array             $context  Contextual data (user, level, config, request).
         */
        $filtered_response = apply_filters(
            'tcn_mlm_membership_confirm_response',
            $response,
            [
                'user_id' => $user_id,
                'level'   => $level,
                'config'  => $config,
                'request' => $request,
            ]
        );

        if ( $filtered_response instanceof WP_Error ) {
            return $filtered_response;
        }

        return is_array( $filtered_response ) ? $filtered_response : $response;
    }
}
