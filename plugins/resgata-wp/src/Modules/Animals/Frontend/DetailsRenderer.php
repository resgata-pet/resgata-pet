<?php
/**
 * Ficha pública do animal.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals\Frontend;

use ResgataWP\Core\TemplateLoader;
use ResgataWP\Modules\Animals\Animal;
use ResgataWP\Modules\Animals\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Monta o HTML da ficha (dados, saúde e galeria) de um animal.
 */
final class DetailsRenderer {

	public const SECTIONS = array( 'facts', 'health', 'gallery' );

	/**
	 * Informa se o visitante atual pode ver o animal.
	 *
	 * @param int $post_id ID do post.
	 */
	public static function can_view( int $post_id ): bool {
		$post = get_post( $post_id );

		if ( ! $post || PostType::SLUG !== $post->post_type || post_password_required( $post ) ) {
			return false;
		}

		return 'publish' === $post->post_status || current_user_can( 'read_post', $post_id );
	}

	/**
	 * Renderiza a ficha.
	 *
	 * @param int    $post_id            ID do animal.
	 * @param string $section            `all` ou uma das seções em SECTIONS.
	 * @param string $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 */
	public static function render( int $post_id, string $section = 'all', string $wrapper_attributes = '' ): string {
		if ( ! self::can_view( $post_id ) ) {
			return '';
		}

		$sections = in_array( $section, self::SECTIONS, true ) ? array( $section ) : self::SECTIONS;

		wp_enqueue_style( 'rwp-frontend' );

		return TemplateLoader::render(
			'animal-details.php',
			array(
				'wrapper_attributes' => '' !== $wrapper_attributes ? $wrapper_attributes : 'class="rwp-animal"',
				'animal'             => new Animal( $post_id ),
				'sections'           => $sections,
			)
		);
	}
}
