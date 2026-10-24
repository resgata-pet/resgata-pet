<?php
/**
 * Números da protetora.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Settings;

use ResgataPet\Core\TemplateLoader;
use ResgataPet\Modules\Animals\Meta;
use ResgataPet\Modules\Animals\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Bloco `resgata-pet/impact`: faixa com os números contados a partir dos cadastros.
 *
 * Fica nas configurações, sempre disponível: cada módulo ligado traz os seus números
 * (os animais daqui, os padrinhos pelo filtro `resgata_impact_items`).
 */
final class Impact {

	/**
	 * Quantos animais publicados estão em cada situação.
	 *
	 * @param string $status Situação (`available`, `adopted`...).
	 */
	private static function count_by_status( string $status ): int {
		$query = new \WP_Query(
			array(
				'post_type'              => PostType::SLUG,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Volume pequeno (animais de uma protetora).
				'meta_key'               => Meta::STATUS,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'             => $status,
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Números mostrados (só os maiores que zero).
	 *
	 * @return array<int, array{icon: string, value: int, label: string, tone: string}>
	 */
	public static function items(): array {
		$items = post_type_exists( PostType::SLUG ) ? self::animal_items() : array();

		/**
		 * Filtra os números da faixa "Números da protetora".
		 *
		 * O módulo de apadrinhamento acrescenta os padrinhos ativos.
		 *
		 * @param array<int, array{icon: string, value: int, label: string, tone: string}> $items Números.
		 */
		$items = (array) apply_filters( 'resgata_impact_items', $items );

		return array_values( array_filter( $items, static fn( $item ): bool => is_array( $item ) && ! empty( $item['value'] ) && ! empty( $item['label'] ) ) );
	}

	/**
	 * Números dos animais (módulo Animais ligado).
	 *
	 * @return array<int, array{icon: string, value: int, label: string, tone: string}>
	 */
	private static function animal_items(): array {
		$total = (int) ( wp_count_posts( PostType::SLUG )->publish ?? 0 );

		$items = array(
			array(
				'icon'  => 'paw-print',
				'value' => $total,
				'label' => _n( 'Animal resgatado', 'Animais resgatados', $total, 'resgata-pet' ),
				'tone'  => 'green',
			),
		);

		$adopted   = $total ? self::count_by_status( 'adopted' ) : 0;
		$available = $total ? self::count_by_status( 'available' ) : 0;

		$items[] = array(
			'icon'  => 'house',
			'value' => $adopted,
			'label' => _n( 'Adoção realizada', 'Adoções realizadas', $adopted, 'resgata-pet' ),
			'tone'  => 'blue',
		);
		$items[] = array(
			'icon'  => 'heart',
			'value' => $available,
			'label' => __( 'Esperando um lar', 'resgata-pet' ),
			'tone'  => 'coral',
		);

		return $items;
	}

	/**
	 * HTML do bloco. Vazio enquanto não houver número para mostrar.
	 *
	 * @param string $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 */
	public static function render( string $wrapper_attributes = '' ): string {
		$items = self::items();

		if ( ! $items ) {
			return '';
		}

		wp_enqueue_style( 'resgata-frontend' );

		return TemplateLoader::render(
			'impact.php',
			array(
				'wrapper_attributes' => '' !== $wrapper_attributes ? $wrapper_attributes : 'class="resgata-impact"',
				'items'              => $items,
			)
		);
	}
}
