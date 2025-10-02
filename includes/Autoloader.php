<?php

namespace TCN\MLM;

class Autoloader {
    private string $rootNamespace = 'TCN\\MLM';

    private string $baseDir;

    public function __construct( string $baseDir ) {
        $this->baseDir = rtrim( $baseDir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
    }

    public function register(): void {
        spl_autoload_register( [ $this, 'loadClass' ] );
    }

    private function loadClass( string $class ): void {
        if ( strpos( $class, $this->rootNamespace ) !== 0 ) {
            return;
        }

        $relative = substr( $class, strlen( $this->rootNamespace ) );
        $relative = ltrim( $relative, '\\' );
        $relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );

        $path = $this->baseDir . $relative . '.php';

        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}
