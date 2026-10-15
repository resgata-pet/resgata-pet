<?php
/**
 * Página de contato.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Cria e localiza a página "Contato", que mostra os dados da protetora
 * com o bloco `resgata-pet/contact`.
 */
final class ContactPage {

	public const OPTION = 'resgata_contact_page_id';

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

		/**
		 * Filtra o conteúdo (blocos) da página "Contato" criada pelo plugin.
		 *
		 * O tema pode devolver um layout completo; o conteúdo precisa ter o
		 * bloco `resgata-pet/contact`, senão vale o padrão do plugin.
		 *
		 * @param string $content Conteúdo em blocos.
		 */
		$content = (string) apply_filters( 'resgata_contact_page_content', '<!-- wp:resgata-pet/contact /-->' );

		if ( ! str_contains( $content, '<!-- wp:resgata-pet/contact ' ) ) {
			$content = '<!-- wp:resgata-pet/contact /-->';
		}

		$page_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => __( 'Contato', 'resgata-pet' ),
					'post_name'    => 'contato',
					'post_content' => $content,
				)
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return null;
		}

		/**
		 * Filtra o modelo de página (template) usado pela página "Contato" criada pelo plugin.
		 *
		 * @param string $template Slug do modelo; vazio usa o modelo padrão de páginas.
		 */
		$template = sanitize_key( (string) apply_filters( 'resgata_contact_page_template', '' ) );

		if ( '' !== $template ) {
			update_post_meta( $page_id, '_wp_page_template', $template );
		}

		update_option( self::OPTION, $page_id, false );

		return get_post( $page_id );
	}
}
