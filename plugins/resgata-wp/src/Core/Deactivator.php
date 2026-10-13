<?php
/**
 * Desativação do plugin.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Tarefas executadas quando o plugin é desativado. Nenhum dado é apagado aqui.
 */
final class Deactivator {

	/**
	 * Remove as regras de URL do plugin.
	 */
	public static function deactivate(): void {
		unregister_post_type( 'resgata_animal' );
		wp_clear_scheduled_hook( 'resgata_stripe_events_cleanup' );
		flush_rewrite_rules();
	}
}
