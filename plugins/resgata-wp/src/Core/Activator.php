<?php
/**
 * Ativação do plugin.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Core;

use ResgataWP\Modules\Animals\PostType;
use ResgataWP\Modules\Animals\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Tarefas executadas quando o plugin é ativado.
 */
final class Activator {

	/**
	 * Prepara o site para o plugin.
	 */
	public static function activate(): void {
		Schema::install();
		Capabilities::grant();

		if ( false === get_option( Modules::OPTION ) ) {
			add_option( Modules::OPTION, Modules::defaults() );
		}

		// Os tipos de conteúdo precisam existir antes de recriar as regras de URL.
		if ( Modules::is_enabled( 'animals' ) ) {
			PostType::register_post_type();
			Taxonomies::register_taxonomies();
			Taxonomies::ensure_default_terms();
		}

		flush_rewrite_rules();
	}
}
