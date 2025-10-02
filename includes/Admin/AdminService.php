<?php

namespace TCN\MLM\Admin;

use TCN\MLM\Contracts\Bootable;
use TCN\MLM\Plugin;

class AdminService implements Bootable {
    private Plugin $plugin;

    public function __construct( Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    public function boot(): void {
        add_action( 'admin_menu', [ $this, 'registerMenu' ] );
        add_action( 'admin_init', [ $this, 'registerSettings' ] );
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'renderMembershipField' ] );
        add_action( 'woocommerce_admin_process_product_object', [ $this, 'saveMembershipField' ] );
    }

    public function registerMenu(): void {
        add_menu_page(
            __( 'TCN MLM', 'tcn-mlm' ),
            __( 'TCN MLM', 'tcn-mlm' ),
            'manage_options',
            'tcn-mlm',
            [ $this, 'renderSettingsPage' ],
            'dashicons-networking',
            58
        );
    }

    public function registerSettings(): void {
        register_setting( 'tcn_mlm_general', 'tcn_mlm_general' );
        register_setting( 'tcn_mlm_levels', 'tcn_mlm_levels' );
    }

    public function renderSettingsPage(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $general = get_option( 'tcn_mlm_general', [] );
        $levels  = tcn_mlm_get_levels();

        ?>
        <div class="wrap tcn-mlm-admin">
            <div class="tcn-mlm-hero">
                <div class="tcn-mlm-hero__brand">
                    <span class="tcn-mlm-hero__logo">TCN</span>
                    <h1><?php esc_html_e( 'TCN MLM Settings', 'tcn-mlm' ); ?></h1>
                </div>
                <p class="tcn-mlm-hero__subtitle">
                    <?php esc_html_e( 'Manage membership tiers, pricing, and default network behaviour.', 'tcn-mlm' ); ?>
                </p>
            </div>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'tcn_mlm_general' );
                do_settings_sections( 'tcn_mlm_general' );
                ?>
                <div class="tcn-mlm-settings">
                    <div class="tcn-mlm-card">
                        <h2><?php esc_html_e( 'General Defaults', 'tcn-mlm' ); ?></h2>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <label for="tcn-mlm-default-sponsor"><?php esc_html_e( 'Default Sponsor ID', 'tcn-mlm' ); ?></label>
                                </th>
                                <td>
                                    <input type="number" name="tcn_mlm_general[default_sponsor_id]" id="tcn-mlm-default-sponsor" value="<?php echo esc_attr( $general['default_sponsor_id'] ?? '' ); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e( 'Used when no sponsor is provided during onboarding.', 'tcn-mlm' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="tcn-mlm-currency"><?php esc_html_e( 'Default Currency', 'tcn-mlm' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" maxlength="3" name="tcn_mlm_general[currency]" id="tcn-mlm-currency" value="<?php echo esc_attr( $general['currency'] ?? 'THB' ); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e( 'Currency code used for membership pricing.', 'tcn-mlm' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="tcn-mlm-card">
                        <h2><?php esc_html_e( 'Membership Catalogue Preview', 'tcn-mlm' ); ?></h2>
                        <p class="description">
                            <?php esc_html_e( 'These tiers feed the mobile app catalogue and seeded products. Update names, pricing, and benefits under Appearance → Editor or via filters.', 'tcn-mlm' ); ?>
                        </p>
                        <div class="tcn-mlm-tier-grid">
                            <?php foreach ( $levels as $key => $config ) :
                                $label       = isset( $config['label'] ) ? (string) $config['label'] : ucfirst( $key );
                                $price       = isset( $config['fee'] ) ? (float) $config['fee'] : 0.0;
                                $currency    = isset( $config['currency'] ) ? (string) $config['currency'] : ( $general['currency'] ?? 'THB' );
                                $description = isset( $config['description'] ) ? (string) $config['description'] : '';
                                $features    = isset( $config['features'] ) && is_array( $config['features'] ) ? $config['features'] : [];
                                ?>
                                <div class="tcn-mlm-tier">
                                    <div class="tcn-mlm-tier__header">
                                        <span class="tcn-mlm-tier__badge"><?php echo esc_html( strtoupper( $key ) ); ?></span>
                                        <h3><?php echo esc_html( $label ); ?></h3>
                                        <p class="tcn-mlm-tier__price">
                                            <?php echo esc_html( sprintf( '%s %s', number_format_i18n( $price, 2 ), strtoupper( $currency ) ) ); ?>
                                            <span><?php esc_html_e( 'per year', 'tcn-mlm' ); ?></span>
                                        </p>
                                    </div>
                                    <?php if ( $description ) : ?>
                                        <p class="tcn-mlm-tier__description"><?php echo esc_html( $description ); ?></p>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $features ) ) : ?>
                                        <ul class="tcn-mlm-tier__features">
                                            <?php foreach ( $features as $feature ) :
                                                if ( ! is_string( $feature ) || '' === trim( $feature ) ) {
                                                    continue;
                                                }
                                                ?>
                                                <li><?php echo esc_html( $feature ); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function renderMembershipField(): void {
        if ( ! function_exists( 'woocommerce_wp_select' ) ) {
            return;
        }

        global $post;

        $product_id = $post ? $post->ID : 0;
        $value      = $product_id ? get_post_meta( $product_id, '_tcn_membership_level', true ) : '';

        woocommerce_wp_select(
            [
                'id'          => 'tcn_mlm_membership_level',
                'label'       => __( 'TCN MLM Membership Level', 'tcn-mlm' ),
                'description' => __( 'Select the membership level this product grants when the related order completes.', 'tcn-mlm' ),
                'options'     => $this->getMembershipLevelOptions(),
                'value'       => $value,
            ]
        );
    }

    public function saveMembershipField( $product ): void {
        if ( ! $product || ! isset( $_POST['tcn_mlm_membership_level'] ) ) {
            return;
        }

        $level = sanitize_key( wp_unslash( $_POST['tcn_mlm_membership_level'] ) );

        $options = $this->getMembershipLevelOptions();

        if ( '' !== $level && ! array_key_exists( $level, $options ) ) {
            $level = '';
        }

        if ( '' === $level ) {
            $product->delete_meta_data( '_tcn_membership_level' );
        } else {
            $product->update_meta_data( '_tcn_membership_level', $level );
        }
    }

    private function getMembershipLevelOptions(): array {
        $levels_option = get_option( 'tcn_mlm_levels', [] );
        $options       = [
            '' => __( '— No membership —', 'tcn-mlm' ),
        ];

        if ( isset( $levels_option['levels'] ) && is_array( $levels_option['levels'] ) ) {
            foreach ( $levels_option['levels'] as $key => $level ) {
                $label = is_array( $level ) && isset( $level['label'] ) ? $level['label'] : ucfirst( $key );
                $options[ sanitize_key( $key ) ] = wp_strip_all_tags( (string) $label );
            }
        } else {
            foreach ( [ 'blue', 'gold', 'platinum', 'black' ] as $key ) {
                $options[ sanitize_key( $key ) ] = ucfirst( $key );
            }
        }

        return $options;
    }
}
