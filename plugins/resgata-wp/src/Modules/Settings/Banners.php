<?php
/**
 * Banners da página inicial.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Settings;

use ResgataWP\Core\Links;
use ResgataWP\Core\TemplateLoader;

defined( 'ABSPATH' ) || exit;

/**
 * Guarda os banners escolhidos no painel (option `resgata_banners`) e renderiza o
 * bloco `resgata-wp/banners`, que os mostra no site.
 */
final class Banners {

	public const OPTION = 'resgata_banners';

	/**
	 * Quantidade máxima de banners.
	 */
	public const MAX = 5;

	/**
	 * Campos de texto de cada banner e seus tamanhos máximos.
	 */
	public const TEXT_FIELDS = array(
		'title'         => 80,
		'highlight'     => 60,
		'text'          => 240,
		'button_label'  => 40,
		'button2_label' => 40,
	);

	/**
	 * Campos de link de cada banner.
	 */
	public const URL_FIELDS = array( 'button_url', 'button2_url' );

	public const URL_MAX = 300;

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		// Depois dos blocos de animais (20), que registram os handles de estilo e script, e antes de o
		// WordPress acrescentar os atributos de suporte (alinhamento, espaçamento), na prioridade 22.
		add_action( 'init', array( $this, 'register_block' ), 21 );
	}

	/**
	 * Registra o bloco.
	 */
	public function register_block(): void {
		if ( ! wp_style_is( 'resgata-frontend', 'registered' ) ) {
			$src = current_theme_supports( 'resgata-wp' ) ? false : RESGATA_PLUGIN_URL . 'assets/css/frontend.css';
			wp_register_style( 'resgata-frontend', $src, array(), RESGATA_VERSION );
		}

		register_block_type( RESGATA_PLUGIN_DIR . 'blocks/banners' );
	}

	/**
	 * Banners salvos, na ordem definida no painel.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get(): array {
		$saved = get_option( self::OPTION, array() );

		return self::sanitize( is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Grava a lista de banners.
	 *
	 * @param array<int, mixed> $banners Banners recebidos.
	 * @return array<int, array<string, mixed>> Lista gravada.
	 */
	public static function update( array $banners ): array {
		$clean = self::sanitize( $banners );

		update_option( self::OPTION, $clean, false );

		return $clean;
	}

	/**
	 * Valida a lista: no máximo MAX itens, textos sem HTML, links seguros e imagem existente.
	 *
	 * @param array<int|string, mixed> $banners Banners recebidos.
	 * @return array<int, array<string, mixed>>
	 */
	public static function sanitize( array $banners ): array {
		$clean = array();

		foreach ( $banners as $banner ) {
			if ( ! is_array( $banner ) || count( $clean ) >= self::MAX ) {
				continue;
			}

			$image_id = absint( $banner['image_id'] ?? 0 );
			$item     = array(
				'image_id' => $image_id && wp_attachment_is_image( $image_id ) ? $image_id : 0,
				'active'   => ! empty( $banner['active'] ),
			);

			foreach ( self::TEXT_FIELDS as $field => $max ) {
				$value          = is_scalar( $banner[ $field ] ?? null ) ? sanitize_text_field( (string) $banner[ $field ] ) : '';
				$item[ $field ] = mb_substr( $value, 0, $max );
			}

			foreach ( self::URL_FIELDS as $field ) {
				$item[ $field ] = self::sanitize_url( $banner[ $field ] ?? '' );
			}

			$clean[] = $item;
		}

		return $clean;
	}

	/**
	 * Link de botão: endereço completo (http/https), caminho do próprio site (`/animais/`) ou âncora (`#contato`).
	 *
	 * @param mixed $value Valor recebido.
	 * @return string Link, ou string vazia se não for aceito.
	 */
	public static function sanitize_url( $value ): string {
		$value = is_scalar( $value ) ? trim( sanitize_text_field( (string) $value ) ) : '';

		if ( '' === $value || mb_strlen( $value ) > self::URL_MAX ) {
			return '';
		}

		// Caminho do próprio site ou âncora ("//" abriria outro site, então não vale).
		if ( preg_match( '~^(/(?!/)|#)[^\s<>"\']*$~', $value ) ) {
			return $value;
		}

		$url = esc_url_raw( $value, array( 'http', 'https' ) );

		// Sem "http(s)://" na frente, o navegador poderia interpretar o link de outro jeito.
		return preg_match( '~^https?://~i', $url ) ? $url : '';
	}

	/**
	 * Banners que aparecem no site: os ativos e com título.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function visible(): array {
		return array_values( array_filter( self::get(), static fn( array $banner ): bool => $banner['active'] && '' !== $banner['title'] ) );
	}

	/**
	 * Banner mostrado enquanto a protetora não cadastra nenhum.
	 *
	 * @return array<string, mixed>
	 */
	public static function fallback(): array {
		$organization = Organization::get();

		return array(
			'image_id'      => 0,
			'active'        => true,
			'title'         => __( 'Juntos por mais', 'resgata-wp' ),
			'highlight'     => __( 'vidas felizes', 'resgata-wp' ),
			'text'          => '' !== $organization['tagline'] ? $organization['tagline'] : __( 'Resgatamos, cuidamos e encontramos novas famílias para animais que precisam de uma segunda chance.', 'resgata-wp' ),
			'button_label'  => '' !== Links::animals() ? __( 'Quero adotar', 'resgata-wp' ) : '',
			'button_url'    => Links::animals(),
			'button2_label' => '' !== Links::sponsorship() ? __( 'Quero ser padrinho', 'resgata-wp' ) : '',
			'button2_url'   => Links::sponsorship(),
		);
	}

	/**
	 * HTML do bloco.
	 *
	 * @param string $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 */
	public static function render( string $wrapper_attributes = '' ): string {
		$banners = self::visible();

		if ( ! $banners ) {
			$banners = array( self::fallback() );
		}

		wp_enqueue_style( 'resgata-frontend' );

		// Setas e bolinhas dependem do script; sem ele, os banners rolam com o dedo.
		if ( count( $banners ) > 1 && wp_script_is( 'resgata-animal-gallery', 'registered' ) ) {
			wp_enqueue_script( 'resgata-animal-gallery' );
		}

		return TemplateLoader::render(
			'banners.php',
			array(
				'wrapper_attributes' => '' !== $wrapper_attributes ? $wrapper_attributes : 'class="resgata-banners"',
				'banners'            => $banners,
				// Na página inicial, o primeiro banner é o título principal da página.
				'first_is_h1'        => is_front_page(),
			)
		);
	}
}
