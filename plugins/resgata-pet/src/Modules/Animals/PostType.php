<?php
/**
 * Post type dos animais.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Animals;

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
			'name'                   => __( 'Animais', 'resgata-pet' ),
			'singular_name'          => __( 'Animal', 'resgata-pet' ),
			'menu_name'              => __( 'Animais', 'resgata-pet' ),
			'add_new'                => __( 'Adicionar animal', 'resgata-pet' ),
			'add_new_item'           => __( 'Adicionar animal', 'resgata-pet' ),
			'edit_item'              => __( 'Editar animal', 'resgata-pet' ),
			'new_item'               => __( 'Novo animal', 'resgata-pet' ),
			'view_item'              => __( 'Ver página do animal', 'resgata-pet' ),
			'view_items'             => __( 'Ver animais', 'resgata-pet' ),
			'search_items'           => __( 'Buscar animais', 'resgata-pet' ),
			'not_found'              => __( 'Nenhum animal cadastrado ainda.', 'resgata-pet' ),
			'not_found_in_trash'     => __( 'Nenhum animal na lixeira.', 'resgata-pet' ),
			'all_items'              => __( 'Todos os animais', 'resgata-pet' ),
			'archives'               => __( 'Animais', 'resgata-pet' ),
			'featured_image'         => __( 'Foto principal', 'resgata-pet' ),
			'set_featured_image'     => __( 'Escolher foto principal', 'resgata-pet' ),
			'remove_featured_image'  => __( 'Remover foto principal', 'resgata-pet' ),
			'use_featured_image'     => __( 'Usar como foto principal', 'resgata-pet' ),
			'item_published'         => __( 'Animal publicado.', 'resgata-pet' ),
			'item_updated'           => __( 'Animal atualizado.', 'resgata-pet' ),
			'item_reverted_to_draft' => __( 'Animal voltou para rascunho.', 'resgata-pet' ),
			'item_trashed'           => __( 'Animal movido para a lixeira.', 'resgata-pet' ),
		);

		register_post_type(
			self::SLUG,
			array(
				'labels'          => $labels,
				'description'     => __( 'Animais resgatados pela protetora.', 'resgata-pet' ),
				'public'          => true,
				// O cadastro é feito só no painel (/painel/animais): sem telas no wp-admin.
				'show_ui'         => false,
				'has_archive'     => true,
				'rewrite'         => array(
					/* translators: slug da URL da listagem de animais. */
					'slug'       => _x( 'animais', 'slug da URL', 'resgata-pet' ),
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
