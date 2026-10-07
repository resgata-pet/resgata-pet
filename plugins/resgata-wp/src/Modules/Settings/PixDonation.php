<?php
/**
 * Doação por Pix no site.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Settings;

use ResgataWP\Core\Assets;
use ResgataWP\Core\Links;
use ResgataWP\Core\TemplateLoader;

defined( 'ABSPATH' ) || exit;

/**
 * Blocos `resgata-wp/pix-donation` (chave, QR Code e "Pix copia e cola"),
 * `resgata-wp/pix-once` (o mesmo, como doação única dentro de outra página) e
 * `resgata-wp/donate-banner` (chamada que leva à página "Como ajudar").
 *
 * A chave vem das Configurações. O QR Code e o código "copia e cola" são
 * montados no navegador (biblioteca `qrcode-pix`); sem o script, a página
 * mostra a chave e o titular, que já bastam para doar.
 */
final class PixDonation {

	public const SCRIPT_HANDLE = 'rwp-pix';

	/**
	 * Valores sugeridos, em reais.
	 */
	private const AMOUNTS = array( 20, 50, 100 );

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		// Depois dos blocos de animais (20), que registram os handles de estilo e script, e antes de o
		// WordPress acrescentar os atributos de suporte (alinhamento, espaçamento), na prioridade 22.
		add_action( 'init', array( $this, 'register_blocks' ), 21 );
	}

	/**
	 * Registra o script e os blocos.
	 */
	public function register_blocks(): void {
		if ( ! wp_style_is( 'rwp-frontend', 'registered' ) ) {
			$src = current_theme_supports( 'resgata-wp' ) ? false : RWP_PLUGIN_URL . 'assets/css/frontend.css';
			wp_register_style( 'rwp-frontend', $src, array(), RWP_VERSION );
		}

		$script = Assets::script( 'wp/pix' );
		if ( $script ) {
			wp_register_script( self::SCRIPT_HANDLE, $script['url'], $script['dependencies'], $script['version'], array( 'strategy' => 'defer' ) );
		}

		register_block_type( RWP_PLUGIN_DIR . 'blocks/pix-donation' );
		register_block_type( RWP_PLUGIN_DIR . 'blocks/pix-once' );
		register_block_type( RWP_PLUGIN_DIR . 'blocks/donate-banner' );
	}

	/**
	 * Valores sugeridos para a doação, em reais.
	 *
	 * @return int[]
	 */
	public static function amounts(): array {
		/**
		 * Filtra os valores sugeridos na doação por Pix.
		 *
		 * @param int[] $amounts Valores em reais.
		 */
		$amounts = (array) apply_filters( 'rwp_pix_amounts', self::AMOUNTS );

		return array_slice( array_values( array_unique( array_filter( array_map( 'absint', $amounts ) ) ) ), 0, 6 );
	}

	/**
	 * HTML do bloco de doação por Pix. Vazio enquanto a chave não for cadastrada.
	 *
	 * @param string $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 * @param bool   $once               Se é a versão "doação única", usada dentro de outras páginas (muda só os textos).
	 */
	public static function render( string $wrapper_attributes = '', bool $once = false ): string {
		$pix = Organization::pix();

		if ( ! $pix ) {
			return '';
		}

		wp_enqueue_style( 'rwp-frontend' );

		if ( wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			wp_enqueue_script( self::SCRIPT_HANDLE );
		}

		return TemplateLoader::render(
			'pix-donation.php',
			array(
				'wrapper_attributes' => '' !== $wrapper_attributes ? $wrapper_attributes : 'id="rwp-pix" class="rwp-pix"',
				'pix'                => $pix,
				'amounts'            => self::amounts(),
				'title'              => $once ? __( 'Prefere doar uma vez só?', 'resgata-wp' ) : __( 'Doe por Pix', 'resgata-wp' ),
				'text'               => $once
					? __( 'Se não dá para ajudar todo mês, faça uma doação única por Pix, de qualquer valor. Ela cai na hora, direto na conta da protetora.', 'resgata-wp' )
					: __( 'Qualquer valor ajuda a pagar ração, vacinas e consultas dos resgatados. A doação cai na hora, direto na conta da protetora.', 'resgata-wp' ),
			)
		);
	}

	/**
	 * HTML do banner "Doe por Pix". Vazio sem chave Pix ou sem a página "Como ajudar".
	 *
	 * Na própria página "Como ajudar" o banner não aparece (ele levaria à mesma página).
	 *
	 * @param string $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 */
	public static function render_banner( string $wrapper_attributes = '' ): string {
		$url = Links::help();

		if ( '' === $url || ! Organization::pix() ) {
			return '';
		}

		if ( is_page() && get_permalink( get_queried_object_id() ) === $url ) {
			return '';
		}

		wp_enqueue_style( 'rwp-frontend' );

		return TemplateLoader::render(
			'donate-banner.php',
			array(
				'wrapper_attributes' => '' !== $wrapper_attributes ? $wrapper_attributes : 'class="rwp-donate"',
				'url'                => $url . '#rwp-pix',
			)
		);
	}
}
