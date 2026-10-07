<?php
/**
 * Endereços das páginas do site que o plugin conhece.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Core;

use ResgataWP\Modules\Settings\ContactPage;

defined( 'ABSPATH' ) || exit;

/**
 * Links para a lista de animais e as páginas de apadrinhamento, "Como ajudar" e contato.
 *
 * Cada método devolve string vazia quando a página não existe, para o
 * template (do plugin ou do tema) simplesmente não mostrar o link.
 */
final class Links {

	/**
	 * Blocos que identificam uma página do site, e onde cada resultado fica guardado por uma hora.
	 */
	private const PAGE_BLOCKS = array(
		'resgata-wp/sponsorship-plans' => 'rwp_sponsorship_page_id',
		'resgata-wp/pix-donation'      => 'rwp_help_page_id',
	);

	/**
	 * Lista pública de animais.
	 */
	public static function animals(): string {
		return post_type_exists( 'rwp_animal' ) ? (string) get_post_type_archive_link( 'rwp_animal' ) : '';
	}

	/**
	 * Página de contato (criada ao salvar as configurações), se estiver publicada.
	 */
	public static function contact(): string {
		$page = ContactPage::get();

		return $page && 'publish' === $page->post_status ? (string) get_permalink( $page ) : '';
	}

	/**
	 * Página publicada que tem o bloco dos planos de apadrinhamento.
	 */
	public static function sponsorship(): string {
		return self::page_with_block( 'resgata-wp/sponsorship-plans' );
	}

	/**
	 * Página "Como ajudar": a página publicada que tem o bloco de doação por Pix.
	 */
	public static function help(): string {
		return self::page_with_block( 'resgata-wp/pix-donation' );
	}

	/**
	 * ID da página "Como ajudar", ou 0 se ela não existir.
	 */
	public static function help_page_id(): int {
		return self::page_id_with_block( 'resgata-wp/pix-donation' );
	}

	/**
	 * Endereço da primeira página publicada cujo conteúdo tem o bloco informado, ou vazio.
	 *
	 * @param string $block Nome do bloco (uma das chaves de PAGE_BLOCKS).
	 */
	private static function page_with_block( string $block ): string {
		$page_id = self::page_id_with_block( $block );

		return $page_id ? (string) get_permalink( $page_id ) : '';
	}

	/**
	 * ID da primeira página publicada cujo conteúdo tem o bloco informado, ou 0.
	 *
	 * @param string $block Nome do bloco (uma das chaves de PAGE_BLOCKS).
	 */
	private static function page_id_with_block( string $block ): int {
		if ( ! isset( self::PAGE_BLOCKS[ $block ] ) || ! \WP_Block_Type_Registry::get_instance()->is_registered( $block ) ) {
			return 0;
		}

		$transient = self::PAGE_BLOCKS[ $block ];
		$page_id   = get_transient( $transient );

		if ( false === $page_id ) {
			$pages = get_posts(
				array(
					'post_type'        => 'page',
					'post_status'      => 'publish',
					'posts_per_page'   => 1,
					'orderby'          => 'ID',
					'order'            => 'ASC',
					'fields'           => 'ids',
					's'                => '<!-- wp:' . $block . ' ',
					'search_columns'   => array( 'post_content' ),
					'suppress_filters' => false,
				)
			);

			$page_id = $pages ? (int) $pages[0] : 0;
			set_transient( $transient, $page_id, HOUR_IN_SECONDS );
		}

		return (int) $page_id;
	}

	/**
	 * Esquece as páginas encontradas (elas são procuradas de novo no próximo uso).
	 */
	public static function forget_pages(): void {
		foreach ( self::PAGE_BLOCKS as $transient ) {
			delete_transient( $transient );
		}
	}
}
