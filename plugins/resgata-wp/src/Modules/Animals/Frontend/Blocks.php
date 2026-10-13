<?php
/**
 * Blocos do módulo de animais.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals\Frontend;

use ResgataWP\Core\Assets;
use ResgataWP\Core\Bindings;
use ResgataWP\Modules\Animals\Options;
use ResgataWP\Modules\Animals\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Registra os blocos (renderizados no servidor) e seus estilos.
 */
final class Blocks {

	public const STYLE_HANDLE   = 'resgata-frontend';
	public const EDITOR_HANDLE  = 'resgata-blocks-editor';
	public const GALLERY_HANDLE = 'resgata-animal-gallery';
	public const SELECT_HANDLE  = 'resgata-site-selects';

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
		$src = current_theme_supports( 'resgata-wp' ) ? false : RESGATA_PLUGIN_URL . 'assets/css/frontend.css';
		wp_register_style( self::STYLE_HANDLE, $src, array(), RESGATA_VERSION );

		$editor = Assets::script( 'wp/blocks-editor' );

		// Sem o build, o handle fica vazio: os blocos continuam funcionando no site.
		wp_register_script(
			self::EDITOR_HANDLE,
			$editor ? $editor['url'] : false,
			$editor ? $editor['dependencies'] : array(),
			$editor ? $editor['version'] : RESGATA_VERSION,
			true
		);

		wp_add_inline_script(
			self::EDITOR_HANDLE,
			'window.resgataBlocks = ' . wp_json_encode(
				array(
					'species'  => Taxonomies::species_options(),
					'statuses' => Options::statuses(),
					'bindings' => Bindings::editor_data(),
				)
			) . ';',
			'before'
		);

		// Setas e miniaturas da galeria da página do animal. Sem o build, a galeria continua rolando com o dedo.
		$gallery = Assets::script( 'wp/animal-gallery' );
		if ( $gallery ) {
			wp_register_script( self::GALLERY_HANDLE, $gallery['url'], $gallery['dependencies'], $gallery['version'], array( 'strategy' => 'defer' ) );
		}

		self::register_select_assets();

		register_block_type( RESGATA_PLUGIN_DIR . 'blocks/animals-list' );
		register_block_type( RESGATA_PLUGIN_DIR . 'blocks/animal-details' );
		register_block_type( RESGATA_PLUGIN_DIR . 'blocks/impact' );
	}

	/**
	 * Registra o script e o estilo que trocam os `<select>` do site pelo Select do shadcn/ui.
	 *
	 * Sem o build, nada é registrado e os campos nativos continuam funcionando.
	 */
	public static function register_select_assets(): void {
		$script = Assets::script( 'site/selects' );

		if ( ! $script || wp_script_is( self::SELECT_HANDLE, 'registered' ) ) {
			return;
		}

		wp_register_script( self::SELECT_HANDLE, $script['url'], $script['dependencies'], $script['version'], array( 'strategy' => 'defer' ) );

		$style = Assets::style_url( 'site/selects' );
		if ( '' !== $style ) {
			wp_register_style( self::SELECT_HANDLE, $style, array(), $script['version'] );
		}
	}

	/**
	 * Carrega o Select do shadcn/ui na página atual (chamado por quem imprime um `<select data-resgata-select>`).
	 */
	public static function enqueue_select_assets(): void {
		self::register_select_assets();

		if ( wp_script_is( self::SELECT_HANDLE, 'registered' ) ) {
			wp_enqueue_script( self::SELECT_HANDLE );
			wp_enqueue_style( self::SELECT_HANDLE );
		}
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
