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
}
