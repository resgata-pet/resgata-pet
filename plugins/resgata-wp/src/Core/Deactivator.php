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
		unregister_post_type( 'rwp_animal' );
		flush_rewrite_rules();
	}
}
