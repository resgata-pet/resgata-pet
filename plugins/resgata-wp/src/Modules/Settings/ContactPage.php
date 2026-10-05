<?php
/**
 * Página de contato.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Cria e localiza a página "Contato", que mostra os dados da protetora
 * com o bloco `resgata-wp/contact`.
 */
final class ContactPage {

	public const OPTION = 'rwp_contact_page_id';

	/**
	 * Página de contato, se existir e não estiver na lixeira.
	 */
	public static function get(): ?\WP_Post {
		$page = get_post( (int) get_option( self::OPTION, 0 ) );

		return $page instanceof \WP_Post && 'page' === $page->post_type && 'trash' !== $page->post_status ? $page : null;
	}

	/**
	 * Dados da página para o painel.
	 *
	 * @return array{id: int, url: string, published: bool}|null
	 */
	public static function summary(): ?array {
		$page = self::get();

		return $page ? array(
			'id'        => $page->ID,
			'url'       => (string) get_permalink( $page ),
			'published' => 'publish' === $page->post_status,
		) : null;
	}

	/**
	 * Cria a página de contato, se ainda não existir e o usuário puder publicar páginas.
	 *
	 * @return \WP_Post|null A página (nova ou existente), ou null se não foi possível criar.
	 */
	public static function ensure(): ?\WP_Post {
		$page = self::get();

		if ( $page || ! current_user_can( 'publish_pages' ) ) {
			return $page;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Contato', 'resgata-wp' ),
				'post_name'    => 'contato',
				'post_content' => '<!-- wp:resgata-wp/contact /-->',
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return null;
		}

		update_option( self::OPTION, $page_id, false );

		return get_post( $page_id );
	}
}
