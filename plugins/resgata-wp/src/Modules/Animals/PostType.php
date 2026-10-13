<?php
/**
 * Post type dos animais.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals;

defined( 'ABSPATH' ) || exit;

/**
 * Registra o post type `resgata_animal`.
 */
final class PostType {

	public const SLUG = 'resgata_animal';

	/**
	 * Registra o post type.
	 */
	public static function register_post_type(): void {
		$labels = array(
			'name'                   => __( 'Animais', 'resgata-wp' ),
			'singular_name'          => __( 'Animal', 'resgata-wp' ),
			'menu_name'              => __( 'Animais', 'resgata-wp' ),
			'add_new'                => __( 'Adicionar animal', 'resgata-wp' ),
			'add_new_item'           => __( 'Adicionar animal', 'resgata-wp' ),
			'edit_item'              => __( 'Editar animal', 'resgata-wp' ),
			'new_item'               => __( 'Novo animal', 'resgata-wp' ),
			'view_item'              => __( 'Ver página do animal', 'resgata-wp' ),
			'view_items'             => __( 'Ver animais', 'resgata-wp' ),
			'search_items'           => __( 'Buscar animais', 'resgata-wp' ),
			'not_found'              => __( 'Nenhum animal cadastrado ainda.', 'resgata-wp' ),
			'not_found_in_trash'     => __( 'Nenhum animal na lixeira.', 'resgata-wp' ),
			'all_items'              => __( 'Todos os animais', 'resgata-wp' ),
			'archives'               => __( 'Animais', 'resgata-wp' ),
			'featured_image'         => __( 'Foto principal', 'resgata-wp' ),
			'set_featured_image'     => __( 'Escolher foto principal', 'resgata-wp' ),
			'remove_featured_image'  => __( 'Remover foto principal', 'resgata-wp' ),
			'use_featured_image'     => __( 'Usar como foto principal', 'resgata-wp' ),
			'item_published'         => __( 'Animal publicado.', 'resgata-wp' ),
			'item_updated'           => __( 'Animal atualizado.', 'resgata-wp' ),
			'item_reverted_to_draft' => __( 'Animal voltou para rascunho.', 'resgata-wp' ),
			'item_trashed'           => __( 'Animal movido para a lixeira.', 'resgata-wp' ),
		);

		register_post_type(
			self::SLUG,
			array(
				'labels'          => $labels,
				'description'     => __( 'Animais resgatados pela protetora.', 'resgata-wp' ),
				'public'          => true,
				// O cadastro é feito só no painel (/painel/animais): sem telas no wp-admin.
				'show_ui'         => false,
				'has_archive'     => true,
				'rewrite'         => array(
					/* translators: slug da URL da listagem de animais. */
					'slug'       => _x( 'animais', 'slug da URL', 'resgata-wp' ),
					'with_front' => false,
				),
				'supports'        => array( 'title', 'editor', 'thumbnail', 'revisions' ),
				'show_in_rest'    => true,
				'rest_base'       => 'animais',
				'capability_type' => array( 'resgata_animal', 'resgata_animals' ),
				'map_meta_cap'    => true,
			)
		);
	}
}
