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

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'TCN MLM Settings', 'tcn-mlm' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'tcn_mlm_general' );
                do_settings_sections( 'tcn_mlm_general' );
                ?>
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
                </table>
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
