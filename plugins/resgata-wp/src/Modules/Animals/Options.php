<?php
/**
 * Listas de opções do cadastro de animais.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals;

defined( 'ABSPATH' ) || exit;

/**
 * Valores permitidos (e seus rótulos) para os campos de escolha.
 */
final class Options {

	public const STATUS_DEFAULT = 'available';

	/**
	 * Situações do animal.
	 *
	 * @return array<string, string>
	 */
	public static function statuses(): array {
		return array(
			'treatment' => __( 'Em tratamento', 'resgata-wp' ),
			'available' => __( 'Disponível para adoção', 'resgata-wp' ),
			'reserved'  => __( 'Reservado', 'resgata-wp' ),
			'adopted'   => __( 'Adotado', 'resgata-wp' ),
			'deceased'  => __( 'Falecido', 'resgata-wp' ),
		);
	}

	/**
	 * Situações mostradas na listagem pública quando nada é informado.
	 *
	 * @return string[]
	 */
	public static function default_public_statuses(): array {
		return array( 'available', 'reserved' );
	}

	/**
	 * Sexo.
	 *
	 * @return array<string, string>
	 */
	public static function sexes(): array {
		return array(
			'female' => __( 'Fêmea', 'resgata-wp' ),
			'male'   => __( 'Macho', 'resgata-wp' ),
		);
	}

	/**
	 * Porte.
	 *
	 * @return array<string, string>
	 */
	public static function sizes(): array {
		return array(
			'small'  => __( 'Pequeno', 'resgata-wp' ),
			'medium' => __( 'Médio', 'resgata-wp' ),
			'large'  => __( 'Grande', 'resgata-wp' ),
		);
	}

	/**
	 * Respostas de sim/não.
	 *
	 * @return array<string, string>
	 */
	public static function yes_no(): array {
		return array(
			'yes' => __( 'Sim', 'resgata-wp' ),
			'no'  => __( 'Ainda não', 'resgata-wp' ),
		);
	}

	/**
	 * Resultados de teste (FIV/FeLV).
	 *
	 * @return array<string, string>
	 */
	public static function test_results(): array {
		return array(
			'negative' => __( 'Negativo', 'resgata-wp' ),
			'positive' => __( 'Positivo', 'resgata-wp' ),
		);
	}

	/**
	 * Tipos de foto da galeria.
	 *
	 * @return array<string, string>
	 */
	public static function photo_types(): array {
		return array(
			'after'  => __( 'Depois', 'resgata-wp' ),
			'before' => __( 'Antes', 'resgata-wp' ),
		);
	}
}
