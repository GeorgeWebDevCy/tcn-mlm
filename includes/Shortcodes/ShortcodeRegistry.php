<?php

namespace TCN\MLM\Shortcodes;

use TCN\MLM\Contracts\Bootable;
use TCN\MLM\Plugin;

class ShortcodeRegistry implements Bootable {
    private Plugin $plugin;

    public function __construct( Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    public function boot(): void {
        add_shortcode( 'tcn_member_dashboard', [ $this, 'renderMemberDashboard' ] );
        add_shortcode( 'tcn_genealogy', [ $this, 'renderGenealogy' ] );
        add_shortcode( 'tcn_mlm_optin', [ $this, 'renderOptin' ] );
    }

    public function renderMemberDashboard( $atts = [], $content = '' ): string {
        if ( ! is_user_logged_in() ) {
            return wp_kses_post( __( 'Please log in to view your dashboard.', 'tcn-mlm' ) );
        }

        $user_id = get_current_user_id();
        $level   = get_user_meta( $user_id, '_tcn_membership_level', true ) ?: 'blue';
        $config  = tcn_mlm_get_level_config( $level );
        $label   = isset( $config['label'] ) ? (string) $config['label'] : ucfirst( $level );
        $benefits = array_filter( isset( $config['benefits'] ) && is_array( $config['benefits'] ) ? $config['benefits'] : [] );

        ob_start();
        ?>
        <div class="tcn-mlm-dashboard">
            <h2><?php esc_html_e( 'Member Dashboard', 'tcn-mlm' ); ?></h2>
            <p><?php esc_html_e( 'This area will display earnings, commission history, and membership status.', 'tcn-mlm' ); ?></p>
            <ul>
                <li><?php esc_html_e( 'Membership Level:', 'tcn-mlm' ); ?> <?php echo esc_html( $label ); ?></li>
                <li><?php esc_html_e( 'Direct Recruits:', 'tcn-mlm' ); ?> <?php echo esc_html( (string) get_user_meta( $user_id, '_tcn_direct_recruits', true ) ); ?></li>
                <li><?php esc_html_e( 'Network Size:', 'tcn-mlm' ); ?> <?php echo esc_html( (string) get_user_meta( $user_id, '_tcn_network_size', true ) ); ?></li>
            </ul>
            <?php if ( ! empty( $benefits ) ) : ?>
                <h3><?php esc_html_e( 'Membership Benefits', 'tcn-mlm' ); ?></h3>
                <ul>
                    <?php foreach ( $benefits as $benefit ) :
                        if ( ! is_array( $benefit ) ) {
                            continue;
                        }

                        $benefit_title = isset( $benefit['title'] ) ? (string) $benefit['title'] : '';

                        if ( '' === trim( $benefit_title ) ) {
                            continue;
                        }

                        $benefit_description = isset( $benefit['description'] ) ? (string) $benefit['description'] : '';
                        $benefit_discount    = isset( $benefit['discountPercentage'] ) ? (float) $benefit['discountPercentage'] : null;
                        ?>
                        <li>
                            <strong><?php echo esc_html( $benefit_title ); ?></strong>
                            <?php if ( $benefit_discount ) : ?>
                                <span><?php printf( esc_html__( ' – %s%% savings', 'tcn-mlm' ), esc_html( (string) $benefit_discount ) ); ?></span>
                            <?php endif; ?>
                            <?php if ( $benefit_description ) : ?>
                                <p><?php echo esc_html( $benefit_description ); ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    public function renderGenealogy(): string {
        if ( ! is_user_logged_in() ) {
            return wp_kses_post( __( 'Please log in to view your genealogy.', 'tcn-mlm' ) );
        }

        ob_start();
        ?>
        <div class="tcn-mlm-genealogy">
            <h2><?php esc_html_e( 'Genealogy', 'tcn-mlm' ); ?></h2>
            <p><?php esc_html_e( 'An interactive genealogy tree will appear here in future releases.', 'tcn-mlm' ); ?></p>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    public function renderOptin(): string {
        ob_start();
        ?>
        <div class="tcn-mlm-optin">
            <form class="tcn-mlm-optin__form">
                <label>
                    <?php esc_html_e( 'Email address', 'tcn-mlm' ); ?>
                    <input type="email" name="tcn_mlm_email" required />
                </label>
                <button type="submit"><?php esc_html_e( 'Join Now', 'tcn-mlm' ); ?></button>
            </form>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}
