<?php
/**
 * Números da protetora.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals\Frontend;

use ResgataWP\Core\TemplateLoader;
use ResgataWP\Modules\Animals\Meta;
use ResgataWP\Modules\Animals\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Bloco `resgata-wp/impact`: faixa com os números contados a partir dos cadastros.
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
		$total = (int) ( wp_count_posts( PostType::SLUG )->publish ?? 0 );

		$items = array(
			array(
				'icon'  => 'paw-print',
				'value' => $total,
				'label' => _n( 'Animal resgatado', 'Animais resgatados', $total, 'resgata-wp' ),
				'tone'  => 'green',
			),
		);

		$adopted   = $total ? self::count_by_status( 'adopted' ) : 0;
		$available = $total ? self::count_by_status( 'available' ) : 0;

		$items[] = array(
			'icon'  => 'house',
			'value' => $adopted,
			'label' => _n( 'Adoção realizada', 'Adoções realizadas', $adopted, 'resgata-wp' ),
			'tone'  => 'blue',
		);
		$items[] = array(
			'icon'  => 'heart',
			'value' => $available,
			'label' => __( 'Esperando um lar', 'resgata-wp' ),
			'tone'  => 'coral',
		);

		/**
		 * Filtra os números da faixa "Números da protetora".
		 *
		 * O módulo de apadrinhamento acrescenta os padrinhos ativos.
		 *
		 * @param array<int, array{icon: string, value: int, label: string, tone: string}> $items Números.
		 */
		$items = (array) apply_filters( 'rwp_impact_items', $items );

		return array_values( array_filter( $items, static fn( $item ): bool => is_array( $item ) && ! empty( $item['value'] ) && ! empty( $item['label'] ) ) );
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

		wp_enqueue_style( 'rwp-frontend' );

		return TemplateLoader::render(
			'impact.php',
			array(
				'wrapper_attributes' => '' !== $wrapper_attributes ? $wrapper_attributes : 'class="rwp-impact"',
				'items'              => $items,
			)
		);
	}
}
