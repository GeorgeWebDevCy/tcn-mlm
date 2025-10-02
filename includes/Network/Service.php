<?php

namespace TCN\MLM\Network;

use TCN\MLM\Contracts\Bootable;
use TCN\MLM\Plugin;

class Service implements Bootable {
    private Plugin $plugin;

    public function __construct( Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    public function boot(): void {
        add_action( 'user_register', [ $this, 'markJoinDate' ], 20 );
    }

    public function markJoinDate( int $user_id ): void {
        if ( ! get_user_meta( $user_id, '_tcn_joined_at', true ) ) {
            update_user_meta( $user_id, '_tcn_joined_at', current_time( 'mysql' ) );
        }
    }
}
