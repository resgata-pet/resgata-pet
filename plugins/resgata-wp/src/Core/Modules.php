<?php
/**
 * Registro dos módulos do plugin.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Lista os módulos e informa quais estão ligados.
 */
final class Modules {

	public const OPTION = 'rwp_modules';

	/**
	 * Módulos já implementados (os demais aparecem como "em breve").
	 *
	 * Fica separado de `all()` porque é consultado antes do hook `init`,
	 * quando as traduções ainda não podem ser carregadas.
	 */
	private const AVAILABLE = array( 'animals', 'adoptions' );

	/**
	 * Todos os módulos previstos no kit.
	 *
	 * @return array<string, array{label: string, description: string, available: bool}>
	 */
	public static function all(): array {
		return array(
			'animals'     => array(
				'label'       => __( 'Animais', 'resgata-wp' ),
				'description' => __( 'Cadastro dos animais resgatados, listagem pública com filtros e página individual de cada animal.', 'resgata-wp' ),
				'available'   => in_array( 'animals', self::AVAILABLE, true ),
			),
			'adoptions'   => array(
				'label'       => __( 'Pedidos de adoção', 'resgata-wp' ),
				'description' => __( 'Questionário público de adoção, enviado pelo WhatsApp, e fila interna de pedidos. Precisa do módulo Animais.', 'resgata-wp' ),
				'available'   => in_array( 'adoptions', self::AVAILABLE, true ),
			),
			'sponsorship' => array(
				'label'       => __( 'Apadrinhamento', 'resgata-wp' ),
				'description' => __( 'Planos mensais de apadrinhamento e doações avulsas.', 'resgata-wp' ),
				'available'   => false,
			),
			'accounting'  => array(
				'label'       => __( 'Prestação de contas', 'resgata-wp' ),
				'description' => __( 'Receitas, despesas e página pública de transparência.', 'resgata-wp' ),
				'available'   => false,
			),
		);
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
	 * Slugs dos módulos ligados.
	 *
	 * @return string[]
	 */
	public static function enabled(): array {
		$enabled = get_option( self::OPTION, self::defaults() );

		return is_array( $enabled ) ? self::sanitize( $enabled ) : self::defaults();
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
			if ( in_array( $slug, self::AVAILABLE, true ) ) {
				$clean[] = $slug;
			}
		}

		return array_values( array_unique( $clean ) );
	}
}
