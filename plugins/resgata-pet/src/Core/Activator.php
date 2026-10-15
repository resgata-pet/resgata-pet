<?php
/**
 * Ativação do plugin.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Core;

use ResgataPet\Modules\Animals\PostType;
use ResgataPet\Modules\Animals\Taxonomies;

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

		if ( false === get_option( Modules::OPTION ) ) {
			add_option( Modules::OPTION, Modules::defaults() );
		}

		// Os tipos de conteúdo precisam existir antes de recriar as regras de URL.
		if ( Modules::is_enabled( 'animals' ) ) {
			PostType::register_post_type();
			Taxonomies::register_taxonomies();
			Taxonomies::ensure_default_terms();
		}

		\ResgataPet\Modules\Panel\Router::add_rewrite_rules();

		flush_rewrite_rules();
	}
}
