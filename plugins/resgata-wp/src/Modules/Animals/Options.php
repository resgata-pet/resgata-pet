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

	/**
	 * Temperamentos sugeridos no cadastro do painel (caixas de seleção).
	 *
	 * São gravados como termos da taxonomia de temperamento, pelo nome.
	 *
	 * @return string[]
	 */
	public static function temperaments(): array {
		$list = array(
			__( 'Dócil', 'resgata-wp' ),
			__( 'Carinhoso(a)', 'resgata-wp' ),
			__( 'Brincalhão(ona)', 'resgata-wp' ),
			__( 'Calmo(a)', 'resgata-wp' ),
			__( 'Tranquilo(a)', 'resgata-wp' ),
			__( 'Ativo(a)', 'resgata-wp' ),
			__( 'Agitado(a)', 'resgata-wp' ),
			__( 'Curioso(a)', 'resgata-wp' ),
			__( 'Sociável', 'resgata-wp' ),
			__( 'Companheiro(a)', 'resgata-wp' ),
			__( 'Independente', 'resgata-wp' ),
			__( 'Tímido(a)', 'resgata-wp' ),
			__( 'Medroso(a)', 'resgata-wp' ),
			__( 'Desconfiado(a) com estranhos', 'resgata-wp' ),
			__( 'Protetor(a)', 'resgata-wp' ),
			__( 'Inteligente', 'resgata-wp' ),
			__( 'Obediente', 'resgata-wp' ),
			__( 'Bagunceiro(a)', 'resgata-wp' ),
			__( 'Dorminhoco(a)', 'resgata-wp' ),
			__( 'Guloso(a)', 'resgata-wp' ),
			__( 'Gosta de colo', 'resgata-wp' ),
			__( 'Gosta de passear', 'resgata-wp' ),
			__( 'Mia ou late bastante', 'resgata-wp' ),
			__( 'Silencioso(a)', 'resgata-wp' ),
			__( 'Se dá bem com crianças', 'resgata-wp' ),
			__( 'Se dá bem com cães', 'resgata-wp' ),
			__( 'Se dá bem com gatos', 'resgata-wp' ),
			__( 'Prefere ser o único animal da casa', 'resgata-wp' ),
			__( 'Adapta-se bem a apartamento', 'resgata-wp' ),
			__( 'Precisa de espaço para correr', 'resgata-wp' ),
			__( 'Precisa de paciência na adaptação', 'resgata-wp' ),
		);

		/**
		 * Filtra a lista de temperamentos sugeridos no painel.
		 *
		 * @param string[] $list Nomes dos temperamentos.
		 */
		return array_values( array_filter( array_map( 'strval', (array) apply_filters( 'resgata_temperament_options', $list ) ) ) );
	}
}
