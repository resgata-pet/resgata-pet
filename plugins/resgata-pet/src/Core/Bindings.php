<?php
/**
 * Fontes de dados para "block bindings".
 *
 * @package ResgataPet
 */

namespace ResgataPet\Core;

use ResgataPet\Modules\Settings\Organization;

defined( 'ABSPATH' ) || exit;

/**
 * Liga atributos de blocos comuns do WordPress (o link de um botão, o texto
 * de um parágrafo) a dados do Resgata Pet.
 *
 * Com isso os modelos e patterns do tema ficam em marcação de blocos pura,
 * editável no Editor do Site, e o endereço certo é resolvido a cada exibição:
 *
 *     <!-- wp:button {"metadata":{"bindings":{"url":{"source":"resgata-pet/link","args":{"key":"help"}}}}} -->
 *
 * Fontes:
 * - `resgata-pet/link`: `animals`, `help`, `donate`, `sponsorship`, `contact`, `home`.
 *   Quando a página pedida não existe, vale a seguinte da lista de reserva, para o botão nunca ficar sem destino.
 * - `resgata-pet/organization`: `name`, `tagline`, `copyright`.
 */
final class Bindings {

	public const LINK_SOURCE         = 'resgata-pet/link';
	public const ORGANIZATION_SOURCE = 'resgata-pet/organization';

	/**
	 * Para cada destino, as páginas tentadas em ordem até achar uma que exista.
	 */
	private const LINK_FALLBACKS = array(
		'animals'     => array( 'animals' ),
		'help'        => array( 'help', 'sponsorship', 'contact' ),
		'donate'      => array( 'donate', 'contact' ),
		'sponsorship' => array( 'sponsorship', 'help', 'contact' ),
		'contact'     => array( 'contact' ),
		'home'        => array(),
	);

	/**
	 * Registra as fontes (no hook `init`).
	 */
	public static function register(): void {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			self::LINK_SOURCE,
			array(
				'label'              => __( 'Página do Resgata Pet', 'resgata-pet' ),
				'get_value_callback' => array( self::class, 'link_value' ),
			)
		);

		register_block_bindings_source(
			self::ORGANIZATION_SOURCE,
			array(
				'label'              => __( 'Dados da protetora', 'resgata-pet' ),
				'get_value_callback' => array( self::class, 'organization_value' ),
			)
		);
	}

	/**
	 * Valores atuais das ligações, para o editor mostrar o texto e o link reais em vez do nome da fonte.
	 *
	 * @return array{links: array<string, string>, organization: array<string, string>}
	 */
	public static function editor_data(): array {
		$links = array();
		foreach ( array_keys( self::LINK_FALLBACKS ) as $key ) {
			$links[ $key ] = self::link_value( array( 'key' => $key ) );
		}

		$organization = array();
		foreach ( array( 'name', 'tagline', 'copyright' ) as $key ) {
			// O editor trata o valor como texto, então vai sem as entidades HTML.
			$organization[ $key ] = html_entity_decode( (string) self::organization_value( array( 'key' => $key ) ), ENT_QUOTES );
		}

		return array(
			'links'        => $links,
			'organization' => $organization,
		);
	}

	/**
	 * Endereço de uma página do site.
	 *
	 * @param string $key `animals`, `help`, `donate`, `sponsorship`, `contact` ou `home`.
	 * @return string Endereço da página, ou string vazia se ela não existir.
	 */
	public static function link( string $key ): string {
		switch ( $key ) {
			case 'animals':
				return Links::animals();
			case 'help':
				return Links::help();
			case 'donate':
				$help = Links::help();
				return '' !== $help ? $help . '#resgata-pix' : '';
			case 'sponsorship':
				return Links::sponsorship();
			case 'contact':
				return Links::contact();
			case 'home':
				return home_url( '/' );
			default:
				return '';
		}
	}

	/**
	 * Valor da fonte `resgata-pet/link`.
	 *
	 * @param mixed $args Argumentos da ligação (`key`).
	 */
	public static function link_value( $args ): string {
		$key = is_array( $args ) && isset( $args['key'] ) ? sanitize_key( (string) $args['key'] ) : '';

		foreach ( self::LINK_FALLBACKS[ $key ] ?? array() as $candidate ) {
			$url = self::link( $candidate );

			if ( '' !== $url ) {
				return $url;
			}
		}

		return home_url( '/' );
	}

	/**
	 * Valor da fonte `resgata-pet/organization`.
	 *
	 * Os textos saem sem HTML; o bloco que os recebe cuida do escape.
	 *
	 * @param mixed $args Argumentos da ligação (`key`).
	 */
	public static function organization_value( $args ): ?string {
		$key  = is_array( $args ) && isset( $args['key'] ) ? sanitize_key( (string) $args['key'] ) : '';
		$data = Organization::get();
		$name = '' !== $data['name'] ? $data['name'] : get_bloginfo( 'name' );

		switch ( $key ) {
			case 'name':
				return esc_html( $name );

			case 'tagline':
				// Sem a frase do painel, vale a "Descrição do site" do WordPress.
				return esc_html( '' !== $data['tagline'] ? $data['tagline'] : get_bloginfo( 'description' ) );

			case 'copyright':
				/* translators: 1: ano, 2: nome da protetora ou do site. */
				return esc_html( sprintf( __( '© %1$s %2$s. Todos os direitos reservados.', 'resgata-pet' ), wp_date( 'Y' ), $name ) );

			default:
				return null;
		}
	}
}
