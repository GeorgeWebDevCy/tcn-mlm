<?php

namespace TCN\MLM\Support;

use TCN\MLM\Plugin;

class Environment {
    private Plugin $plugin;

    public function __construct( Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    public function version(): string {
        return defined( 'TCN_MLM_VERSION' ) ? TCN_MLM_VERSION : '0.0.0';
    }

    public function pluginFile(): string {
        return defined( 'TCN_MLM_PLUGIN_FILE' ) ? TCN_MLM_PLUGIN_FILE : __FILE__;
    }

    public function pluginDir(): string {
        return defined( 'TCN_MLM_PLUGIN_DIR' ) ? TCN_MLM_PLUGIN_DIR : dirname( $this->pluginFile() ) . '/';
    }

    public function pluginUrl(): string {
        return defined( 'TCN_MLM_PLUGIN_URL' ) ? TCN_MLM_PLUGIN_URL : '';
    }

    public function textDomain(): string {
        return 'tcn-mlm';
    }

    public function option( string $key, $default = null ) {
        $options = get_option( 'tcn_mlm_general', [] );

        return $options[ $key ] ?? $default;
    }
}
