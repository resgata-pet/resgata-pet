<?php
/**
 * Blocos do módulo de animais.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals\Frontend;

use ResgataWP\Modules\Animals\Options;
use ResgataWP\Modules\Animals\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Registra os blocos (renderizados no servidor) e seus estilos.
 */
final class Blocks {

	public const STYLE_HANDLE  = 'rwp-frontend';
	public const EDITOR_HANDLE = 'rwp-blocks-editor';

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		// Depois das taxonomias, para o editor receber a lista de espécies.
		add_action( 'init', array( $this, 'register_blocks' ), 20 );
		add_filter( 'block_categories_all', array( $this, 'add_category' ) );
	}

	/**
	 * Registra estilos, script do editor e os blocos.
	 */
	public function register_blocks(): void {
		/*
		 * Se o tema declarar `add_theme_support( 'resgata-wp' )`, ele assume o
		 * visual: registramos o handle vazio para os blocos não carregarem CSS.
		 */
		$src = current_theme_supports( 'resgata-wp' ) ? false : RWP_PLUGIN_URL . 'assets/css/frontend.css';
		wp_register_style( self::STYLE_HANDLE, $src, array(), RWP_VERSION );

		wp_register_script(
			self::EDITOR_HANDLE,
			RWP_PLUGIN_URL . 'assets/js/blocks-editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			RWP_VERSION,
			true
		);

		wp_add_inline_script(
			self::EDITOR_HANDLE,
			'window.rwpBlocks = ' . wp_json_encode(
				array(
					'species'  => Taxonomies::species_options(),
					'statuses' => Options::statuses(),
				)
			) . ';',
			'before'
		);

		register_block_type( RWP_PLUGIN_DIR . 'blocks/animals-list' );
		register_block_type( RWP_PLUGIN_DIR . 'blocks/animal-details' );
	}

	/**
	 * Adiciona a categoria "Resgata WP" no inseridor de blocos.
	 *
	 * @param array<int, array<string, mixed>> $categories Categorias existentes.
	 * @return array<int, array<string, mixed>>
	 */
	public function add_category( array $categories ): array {
		$categories[] = array(
			'slug'  => 'resgata-wp',
			'title' => __( 'Resgata WP', 'resgata-wp' ),
			'icon'  => null,
		);

		return $categories;
	}
}
