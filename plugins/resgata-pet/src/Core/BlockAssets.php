<?php
/**
 * Arquivos comuns a todos os blocos do plugin.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Core;

use ResgataPet\Modules\Animals\Options;
use ResgataPet\Modules\Animals\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Registra o estilo do site, o script do editor e a categoria "Resgata Pet".
 *
 * Fica fora dos módulos: os blocos de qualquer módulo ligado (até num site só com
 * apadrinhamento, sem o de animais) dependem desses handles.
 */
final class BlockAssets {

	public const STYLE_HANDLE  = 'resgata-frontend';
	public const EDITOR_HANDLE = 'resgata-blocks-editor';

	/**
	 * Registra os hooks (chamado no bootstrap, antes do `init`).
	 */
	public static function register(): void {
		// Antes dos blocos (prioridades 20 e 21), que apontam para estes handles no block.json.
		add_action( 'init', array( self::class, 'register_assets' ), 19 );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'add_editor_data' ) );
		add_filter( 'block_categories_all', array( self::class, 'add_category' ) );
	}

	/**
	 * Registra o estilo do site e o script do editor.
	 */
	public static function register_assets(): void {
		/*
		 * Se o tema declarar `add_theme_support( 'resgata-pet' )`, ele assume o
		 * visual: registramos o handle vazio para os blocos não carregarem CSS.
		 */
		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			$src = current_theme_supports( 'resgata-pet' ) ? false : RESGATA_PLUGIN_URL . 'assets/css/frontend.css';
			wp_register_style( self::STYLE_HANDLE, $src, array(), RESGATA_VERSION );
		}

		$editor = Assets::script( 'wp/blocks-editor' );

		// Sem o build, o handle fica vazio: os blocos continuam funcionando no site.
		wp_register_script(
			self::EDITOR_HANDLE,
			$editor ? $editor['url'] : false,
			$editor ? $editor['dependencies'] : array(),
			$editor ? $editor['version'] : RESGATA_VERSION,
			true
		);
	}

	/**
	 * Dados do editor: listas de opções e os valores atuais das ligações de blocos.
	 *
	 * Montados só ao abrir o editor, depois que todos os blocos foram registrados
	 * (os links das páginas dependem dos blocos que existem).
	 */
	public static function add_editor_data(): void {
		wp_add_inline_script(
			self::EDITOR_HANDLE,
			'window.resgataBlocks = ' . wp_json_encode(
				array(
					'species'  => taxonomy_exists( Taxonomies::SPECIES ) ? Taxonomies::species_options() : array(),
					'statuses' => Options::statuses(),
					'bindings' => Bindings::editor_data(),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Adiciona a categoria "Resgata Pet" no inseridor de blocos.
	 *
	 * @param array<int, array<string, mixed>> $categories Categorias existentes.
	 * @return array<int, array<string, mixed>>
	 */
	public static function add_category( array $categories ): array {
		$categories[] = array(
			'slug'  => 'resgata-pet',
			'title' => __( 'Resgata Pet', 'resgata-pet' ),
			'icon'  => null,
		);

		return $categories;
	}
}
