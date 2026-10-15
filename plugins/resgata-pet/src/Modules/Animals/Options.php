<?php
/**
 * Listas de opções do cadastro de animais.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Animals;

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
			'treatment' => __( 'Em tratamento', 'resgata-pet' ),
			'available' => __( 'Disponível para adoção', 'resgata-pet' ),
			'reserved'  => __( 'Reservado', 'resgata-pet' ),
			'adopted'   => __( 'Adotado', 'resgata-pet' ),
			'deceased'  => __( 'Falecido', 'resgata-pet' ),
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
			'female' => __( 'Fêmea', 'resgata-pet' ),
			'male'   => __( 'Macho', 'resgata-pet' ),
		);
	}

	/**
	 * Porte.
	 *
	 * @return array<string, string>
	 */
	public static function sizes(): array {
		return array(
			'small'  => __( 'Pequeno', 'resgata-pet' ),
			'medium' => __( 'Médio', 'resgata-pet' ),
			'large'  => __( 'Grande', 'resgata-pet' ),
		);
	}

	/**
	 * Respostas de sim/não.
	 *
	 * @return array<string, string>
	 */
	public static function yes_no(): array {
		return array(
			'yes' => __( 'Sim', 'resgata-pet' ),
			'no'  => __( 'Ainda não', 'resgata-pet' ),
		);
	}

	/**
	 * Resultados de teste (FIV/FeLV).
	 *
	 * @return array<string, string>
	 */
	public static function test_results(): array {
		return array(
			'negative' => __( 'Negativo', 'resgata-pet' ),
			'positive' => __( 'Positivo', 'resgata-pet' ),
		);
	}

	/**
	 * Tipos de foto da galeria.
	 *
	 * @return array<string, string>
	 */
	public static function photo_types(): array {
		return array(
			'after'  => __( 'Depois', 'resgata-pet' ),
			'before' => __( 'Antes', 'resgata-pet' ),
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
			__( 'Dócil', 'resgata-pet' ),
			__( 'Carinhoso(a)', 'resgata-pet' ),
			__( 'Brincalhão(ona)', 'resgata-pet' ),
			__( 'Calmo(a)', 'resgata-pet' ),
			__( 'Tranquilo(a)', 'resgata-pet' ),
			__( 'Ativo(a)', 'resgata-pet' ),
			__( 'Agitado(a)', 'resgata-pet' ),
			__( 'Curioso(a)', 'resgata-pet' ),
			__( 'Sociável', 'resgata-pet' ),
			__( 'Companheiro(a)', 'resgata-pet' ),
			__( 'Independente', 'resgata-pet' ),
			__( 'Tímido(a)', 'resgata-pet' ),
			__( 'Medroso(a)', 'resgata-pet' ),
			__( 'Desconfiado(a) com estranhos', 'resgata-pet' ),
			__( 'Protetor(a)', 'resgata-pet' ),
			__( 'Inteligente', 'resgata-pet' ),
			__( 'Obediente', 'resgata-pet' ),
			__( 'Bagunceiro(a)', 'resgata-pet' ),
			__( 'Dorminhoco(a)', 'resgata-pet' ),
			__( 'Guloso(a)', 'resgata-pet' ),
			__( 'Gosta de colo', 'resgata-pet' ),
			__( 'Gosta de passear', 'resgata-pet' ),
			__( 'Mia ou late bastante', 'resgata-pet' ),
			__( 'Silencioso(a)', 'resgata-pet' ),
			__( 'Se dá bem com crianças', 'resgata-pet' ),
			__( 'Se dá bem com cães', 'resgata-pet' ),
			__( 'Se dá bem com gatos', 'resgata-pet' ),
			__( 'Prefere ser o único animal da casa', 'resgata-pet' ),
			__( 'Adapta-se bem a apartamento', 'resgata-pet' ),
			__( 'Precisa de espaço para correr', 'resgata-pet' ),
			__( 'Precisa de paciência na adaptação', 'resgata-pet' ),
		);

		/**
		 * Filtra a lista de temperamentos sugeridos no painel.
		 *
		 * @param string[] $list Nomes dos temperamentos.
		 */
		return array_values( array_filter( array_map( 'strval', (array) apply_filters( 'resgata_temperament_options', $list ) ) ) );
	}
}
