<?php
/**
 * Registro dos módulos do plugin.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Lista os módulos e informa quais estão ligados.
 */
final class Modules {

	public const OPTION = 'resgata_modules';

	/**
	 * Módulos já implementados (os demais aparecem como "em breve").
	 *
	 * Fica separado de `all()` porque é consultado antes do hook `init`,
	 * quando as traduções ainda não podem ser carregadas.
	 */
	private const AVAILABLE = array( 'animals', 'adoptions', 'sponsorship' );

	/**
	 * Módulos que só funcionam junto com outro (os pedidos de adoção são sempre de um animal).
	 */
	private const REQUIRES = array(
		'adoptions' => 'animals',
	);

	/**
	 * Módulos que podem ser ligados neste site.
	 *
	 * O apadrinhamento usa a biblioteca da Stripe (pasta `vendor/`): sem ela, fica indisponível.
	 *
	 * @return string[]
	 */
	private static function available(): array {
		if ( class_exists( \Stripe\StripeClient::class ) ) {
			return self::AVAILABLE;
		}

		return array_values( array_diff( self::AVAILABLE, array( 'sponsorship' ) ) );
	}

	/**
	 * Todos os módulos previstos no kit.
	 *
	 * @return array<string, array{label: string, description: string, available: bool, requires: string}>
	 */
	public static function all(): array {
		return array(
			'animals'     => array(
				'label'       => __( 'Animais', 'resgata-pet' ),
				'description' => __( 'Cadastro dos animais resgatados, listagem pública com filtros e página individual de cada animal.', 'resgata-pet' ),
				'available'   => in_array( 'animals', self::available(), true ),
				'requires'    => '',
			),
			'adoptions'   => array(
				'label'       => __( 'Pedidos de adoção', 'resgata-pet' ),
				'description' => __( 'Questionário público de adoção, enviado pelo WhatsApp, e fila interna de pedidos. Precisa do módulo Animais.', 'resgata-pet' ),
				'available'   => in_array( 'adoptions', self::available(), true ),
				'requires'    => self::REQUIRES['adoptions'],
			),
			'sponsorship' => array(
				'label'       => __( 'Apadrinhamento', 'resgata-pet' ),
				'description' => __( 'Planos mensais de apadrinhamento pagos com cartão pela Stripe, com contador de padrinhos no site.', 'resgata-pet' ),
				'available'   => in_array( 'sponsorship', self::available(), true ),
				'requires'    => '',
			),
			'accounting'  => array(
				'label'       => __( 'Prestação de contas', 'resgata-pet' ),
				'description' => __( 'Receitas, despesas e página pública de transparência.', 'resgata-pet' ),
				'available'   => false,
				'requires'    => '',
			),
		);
	}

	/**
	 * Slugs dos módulos que podem ser ligados neste site (para validar o que chega do painel).
	 *
	 * @return string[]
	 */
	public static function choices(): array {
		return self::available();
	}

	/**
	 * Módulos ligados por padrão em uma instalação nova.
	 *
	 * @return string[]
	 */
	public static function defaults(): array {
		return array( 'animals', 'adoptions' );
	}

	/**
	 * Módulos marcados nas configurações, mesmo os que esperam por outro módulo desligado.
	 *
	 * É o que as telas de escolha mostram: desligar Animais e ligar de novo traz os
	 * pedidos de adoção de volta como estavam.
	 *
	 * @return string[]
	 */
	public static function chosen(): array {
		$chosen = get_option( self::OPTION, self::defaults() );

		return is_array( $chosen ) ? self::sanitize( $chosen ) : self::defaults();
	}

	/**
	 * Slugs dos módulos ligados e em funcionamento (com os módulos de que dependem também ligados).
	 *
	 * @return string[]
	 */
	public static function enabled(): array {
		$chosen = self::chosen();

		return array_values(
			array_filter(
				$chosen,
				static fn( string $slug ): bool => ! isset( self::REQUIRES[ $slug ] ) || in_array( self::REQUIRES[ $slug ], $chosen, true )
			)
		);
	}

	/**
	 * Informa se um módulo está ligado.
	 *
	 * @param string $slug Slug do módulo.
	 */
	public static function is_enabled( string $slug ): bool {
		return in_array( $slug, self::enabled(), true );
	}

	/**
	 * Mantém somente slugs de módulos existentes e já disponíveis.
	 *
	 * @param mixed $value Valor enviado pelo formulário de configurações.
	 * @return string[]
	 */
	public static function sanitize( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$clean = array();

		foreach ( $value as $slug ) {
			$slug = is_scalar( $slug ) ? sanitize_key( (string) $slug ) : '';
			if ( in_array( $slug, self::available(), true ) ) {
				$clean[] = $slug;
			}
		}

		return array_values( array_unique( $clean ) );
	}
}
