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

        $direct_recruits = absint( get_user_meta( $user_id, '_tcn_direct_recruits', true ) );
        $network_size    = absint( get_user_meta( $user_id, '_tcn_network_size', true ) );

        $benefits_markup = '';

        if ( ! empty( $benefits ) ) {
            ob_start();
            ?>
            <div class="tcn-mlm-dashboard__benefits">
                <h3><?php esc_html_e( 'Membership Benefits', 'tcn-mlm' ); ?></h3>
                <div class="tcn-mlm-dashboard__benefit-grid">
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
                        <div class="tcn-mlm-dashboard__benefit">
                            <div class="tcn-mlm-dashboard__benefit-badge">
                                <?php if ( $benefit_discount ) : ?>
                                    <span><?php echo esc_html( sprintf( '%s%%', $benefit_discount ) ); ?></span>
                                <?php else : ?>
                                    <span><?php esc_html_e( 'Perk', 'tcn-mlm' ); ?></span>
                                <?php endif; ?>
                            </div>
                            <h4><?php echo esc_html( $benefit_title ); ?></h4>
                            <?php if ( $benefit_description ) : ?>
                                <p><?php echo esc_html( $benefit_description ); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            $benefits_markup = (string) ob_get_clean();
        }

        ob_start();
        ?>
        <div class="tcn-mlm-dashboard">
            <div class="tcn-mlm-dashboard__hero">
                <div class="tcn-mlm-dashboard__hero-background"></div>
                <div class="tcn-mlm-dashboard__hero-content">
                    <span class="tcn-mlm-dashboard__tier-badge"><?php echo esc_html( strtoupper( $level ) ); ?></span>
                    <h2><?php echo esc_html( $label ); ?></h2>
                    <p><?php esc_html_e( 'Track your network performance and unlock the next tier of rewards.', 'tcn-mlm' ); ?></p>
                </div>
            </div>
            <div class="tcn-mlm-dashboard__stats">
                <div class="tcn-mlm-dashboard__stat">
                    <span class="tcn-mlm-dashboard__stat-label"><?php esc_html_e( 'Direct Recruits', 'tcn-mlm' ); ?></span>
                    <span class="tcn-mlm-dashboard__stat-value"><?php echo esc_html( (string) $direct_recruits ); ?></span>
                </div>
                <div class="tcn-mlm-dashboard__stat">
                    <span class="tcn-mlm-dashboard__stat-label"><?php esc_html_e( 'Network Size', 'tcn-mlm' ); ?></span>
                    <span class="tcn-mlm-dashboard__stat-value"><?php echo esc_html( (string) $network_size ); ?></span>
                </div>
                <div class="tcn-mlm-dashboard__stat">
                    <span class="tcn-mlm-dashboard__stat-label"><?php esc_html_e( 'Membership Level', 'tcn-mlm' ); ?></span>
                    <span class="tcn-mlm-dashboard__stat-value"><?php echo esc_html( $label ); ?></span>
                </div>
            </div>
            <?php echo $benefits_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php

        $output = (string) ob_get_clean();

        wp_enqueue_style( 'tcn-mlm-frontend', TCN_MLM_PLUGIN_URL . 'assets/css/frontend.css', [], TCN_MLM_VERSION );

        return $output;
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
