<?php
/**
 * Blocos do módulo de animais.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Animals\Frontend;

use ResgataPet\Core\Assets;
use ResgataPet\Core\BlockAssets;

defined( 'ABSPATH' ) || exit;

/**
 * Registra os blocos de animais (renderizados no servidor) e os scripts do site.
 */
final class Blocks {

	public const STYLE_HANDLE   = BlockAssets::STYLE_HANDLE;
	public const EDITOR_HANDLE  = BlockAssets::EDITOR_HANDLE;
	public const GALLERY_HANDLE = 'resgata-animal-gallery';
	public const SELECT_HANDLE  = 'resgata-site-selects';

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		// Depois das taxonomias. O estilo, o script do editor e a categoria são do Core\BlockAssets.
		add_action( 'init', array( $this, 'register_blocks' ), 20 );
	}

	/**
	 * Registra os scripts do site e os blocos.
	 */
	public function register_blocks(): void {
		// Setas e miniaturas da galeria da página do animal. Sem o build, a galeria continua rolando com o dedo.
		$gallery = Assets::script( 'wp/animal-gallery' );
		if ( $gallery ) {
			wp_register_script( self::GALLERY_HANDLE, $gallery['url'], $gallery['dependencies'], $gallery['version'], array( 'strategy' => 'defer' ) );
		}

		self::register_select_assets();

		register_block_type( RESGATA_PLUGIN_DIR . 'blocks/animals-list' );
		register_block_type( RESGATA_PLUGIN_DIR . 'blocks/animal-details' );
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
}
