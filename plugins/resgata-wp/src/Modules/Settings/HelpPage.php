<?php
/**
 * Página "Como ajudar" do site.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Settings;

use ResgataWP\Core\Links;

defined( 'ABSPATH' ) || exit;

/**
 * Cria sozinha a página "Como ajudar" quando a chave Pix é cadastrada.
 *
 * A página é a que tem o bloco `resgata-wp/pix-donation`. Se a protetora já
 * tiver montado uma, ela é aproveitada; se apagar a página criada, ela nasce
 * de novo no próximo salvamento das configurações.
 */
final class HelpPage {

	public const OPTION = 'rwp_help_page_id';

	/**
	 * Página "Como ajudar", se existir e não estiver na lixeira.
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
	 * Garante a página "Como ajudar" quando há chave Pix e o usuário pode publicar páginas.
	 *
	 * @return \WP_Post|null A página (nova ou existente), ou null se não há o que criar.
	 */
	public static function ensure(): ?\WP_Post {
		$page = self::get();

		if ( $page || ! Organization::pix() ) {
			return $page;
		}

		// A protetora já montou uma página com o bloco de doação: é ela que vale.
		$existing = Links::help_page_id();
		if ( $existing ) {
			update_option( self::OPTION, $existing, false );

			return get_post( $existing );
		}

		if ( ! current_user_can( 'publish_pages' ) ) {
			return null;
		}

		/**
		 * Filtra o conteúdo (blocos) da página "Como ajudar" criada pelo plugin.
		 *
		 * O tema pode devolver um layout completo; o conteúdo precisa ter o
		 * bloco `resgata-wp/pix-donation`, senão vale o padrão do plugin.
		 *
		 * @param string $content Conteúdo em blocos.
		 */
		$content = (string) apply_filters( 'rwp_help_page_content', '<!-- wp:resgata-wp/pix-donation /-->' );

		if ( ! str_contains( $content, '<!-- wp:resgata-wp/pix-donation ' ) ) {
			$content = '<!-- wp:resgata-wp/pix-donation /-->';
		}

		$page_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => __( 'Como ajudar', 'resgata-wp' ),
					'post_name'    => 'como-ajudar',
					'post_content' => $content,
				)
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return null;
		}

		/**
		 * Filtra o modelo de página (template) usado pela página "Como ajudar" criada pelo plugin.
		 *
		 * @param string $template Slug do modelo; vazio usa o modelo padrão de páginas.
		 */
		$template = sanitize_key( (string) apply_filters( 'rwp_help_page_template', '' ) );

		if ( '' !== $template ) {
			update_post_meta( $page_id, '_wp_page_template', $template );
		}

		update_option( self::OPTION, $page_id, false );
		Links::forget_pages();

		return get_post( $page_id );
	}
}
