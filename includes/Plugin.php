<?php

namespace TCN\MLM;

use TCN\MLM\Contracts\Bootable;
use TCN\MLM\Assets;

class Plugin {
    private static ?Plugin $instance = null;

    /**
     * @var array<class-string, object>
     */
    private array $services = [];

    private bool $booted = false;

    private function __construct() {}

    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new Plugin();
        }

        return self::$instance;
    }

    public function boot(): void {
        if ( $this->booted ) {
            return;
        }

        $this->booted = true;

        $this->register_default_services();
    }

    public function register_service( string $service_class, ?object $service = null ): object {
        if ( isset( $this->services[ $service_class ] ) ) {
            return $this->services[ $service_class ];
        }

        if ( null === $service ) {
            $service = new $service_class( $this );
        }

        if ( $service instanceof Bootable ) {
            $service->boot();
        }

        $this->services[ $service_class ] = $service;

        return $service;
    }

    public function has( string $service_class ): bool {
        return isset( $this->services[ $service_class ] );
    }

    public function get( string $service_class ): object {
        if ( ! isset( $this->services[ $service_class ] ) ) {
            throw new \RuntimeException( sprintf( 'Service %s has not been registered.', $service_class ) );
        }

        return $this->services[ $service_class ];
    }

    private function register_default_services(): void {
        $this->register_service( Support\Environment::class );
        Assets::register_frontend();
        $this->register_service( Membership\Manager::class );
        $this->register_service( Network\Service::class );
        $this->register_service( Commission\Manager::class );
        $this->register_service( Shortcodes\ShortcodeRegistry::class );
        $this->register_service( Rest\Api::class );
        $this->register_service( Rest\MembershipsController::class );
        $this->register_service( Admin\AdminService::class );
        $this->register_service( WooCommerce\AccountEndpoints::class );
    }
}
