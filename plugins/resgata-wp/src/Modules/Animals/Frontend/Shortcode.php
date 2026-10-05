<?php
/**
 * Shortcode da listagem de animais.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode `[rwp_animais]`, para temas e editores sem blocos.
 *
 * Exemplo: `[rwp_animais especie="gato" quantidade="6" colunas="3" filtros="nao"]`
 */
final class Shortcode {

	public const TAG = 'rwp_animais';

	/**
	 * Situações aceitas no atributo `status`, em português.
	 */
	private const STATUS_MAP = array(
		'em-tratamento' => 'treatment',
		'disponivel'    => 'available',
		'reservado'     => 'reserved',
		'adotado'       => 'adopted',
		'falecido'      => 'deceased',
	);

	/**
	 * Registra o shortcode.
	 */
	public function register(): void {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Renderiza o shortcode.
	 *
	 * @param array<string, string>|string $atts Atributos informados.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'especie'    => '',
				'status'     => 'disponivel,reservado',
				'quantidade' => 12,
				'colunas'    => 3,
				'filtros'    => 'sim',
				'paginacao'  => 'sim',
			),
			is_array( $atts ) ? $atts : array(),
			self::TAG
		);

		$statuses = array();
		foreach ( explode( ',', (string) $atts['status'] ) as $status ) {
			$status = sanitize_title( $status );
			if ( isset( self::STATUS_MAP[ $status ] ) ) {
				$statuses[] = self::STATUS_MAP[ $status ];
			}
		}

		return ListRenderer::render(
			array(
				'species'         => (string) $atts['especie'],
				'statuses'        => $statuses,
				'per_page'        => $atts['quantidade'],
				'columns'         => $atts['colunas'],
				'show_filters'    => self::is_yes( (string) $atts['filtros'] ),
				'show_pagination' => self::is_yes( (string) $atts['paginacao'] ),
			)
		);
	}

	/**
	 * Interpreta "sim"/"não".
	 *
	 * @param string $value Valor do atributo.
	 */
	private static function is_yes( string $value ): bool {
		return in_array( sanitize_title( $value ), array( 'sim', 's', '1', 'true', 'yes' ), true );
	}
}
