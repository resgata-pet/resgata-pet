<?php
/**
 * Módulo de apadrinhamento.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Sponsorship;

use ResgataPet\Modules\Sponsorship\CLI\SetupCommand;

defined( 'ABSPATH' ) || exit;

/**
 * Liga as partes do módulo de apadrinhamento aos hooks do WordPress.
 */
final class SponsorshipModule {

	/**
	 * Registra os hooks do módulo.
	 */
	public function register(): void {
		( new Blocks() )->register();
		( new Checkout() )->register();
		( new MagicLink() )->register();
		( new ThanksPage() )->register();

		add_action( 'rest_api_init', array( new WebhookController(), 'register_routes' ) );

		add_action( Events::CLEANUP_HOOK, array( Events::class, 'cleanup' ) );
		add_action( Events::CLEANUP_HOOK, array( Payments::class, 'cleanup' ) );
		add_action( 'init', array( $this, 'schedule_cleanup' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'resgata stripe-setup', SetupCommand::class );
		}
	}

	/**
	 * Agenda a limpeza diária dos IDs de eventos antigos.
	 */
	public function schedule_cleanup(): void {
		if ( ! wp_next_scheduled( Events::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', Events::CLEANUP_HOOK );
		}
	}
}
