<?php
/**
 * Módulo de pedidos de adoção.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Adoptions;

defined( 'ABSPATH' ) || exit;

/**
 * Liga as partes do módulo de adoção aos hooks do WordPress.
 */
final class AdoptionsModule {

	/**
	 * Registra os hooks do módulo.
	 */
	public function register(): void {
		add_action( 'init', array( PostType::class, 'register_post_type' ) );
		add_action( 'before_delete_post', array( $this, 'delete_requests_of_animal' ) );

		( new Form() )->register();
		( new Submission() )->register();
	}

	/**
	 * Ao apagar um animal em definitivo, apaga também os pedidos (dados pessoais) ligados a ele.
	 *
	 * @param int $post_id ID do post que está sendo apagado.
	 */
	public function delete_requests_of_animal( int $post_id ): void {
		if ( \ResgataPet\Modules\Animals\PostType::SLUG !== get_post_type( $post_id ) ) {
			return;
		}

		$requests = get_posts(
			array(
				'post_type'      => PostType::SLUG,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Roda só ao apagar um animal.
				'meta_key'       => PostType::META_ANIMAL,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'     => $post_id,
			)
		);

		foreach ( $requests as $request_id ) {
			wp_delete_post( (int) $request_id, true );
		}
	}
}
