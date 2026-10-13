<?php
/**
 * Post type dos pedidos de adoção.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Adoptions;

use ResgataWP\Core\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registra o post type `resgata_adoption`.
 *
 * Os pedidos têm dados pessoais: o post type não é público, não tem tela no
 * wp-admin, não aparece na REST do WordPress nem em buscas. O único acesso é
 * pela API do painel, com a capability `resgata_manage_adoptions`.
 */
final class PostType {

	public const SLUG = 'resgata_adoption';

	public const META_STATUS  = 'resgata_adoption_status';
	public const META_ANIMAL  = 'resgata_adoption_animal_id';
	public const META_ANSWERS = 'resgata_adoption_answers';
	public const META_CONSENT = 'resgata_adoption_consent';
	public const META_NOTES   = 'resgata_adoption_notes';

	public const STATUS_DEFAULT = 'new';

	/**
	 * Situações do pedido na fila interna.
	 *
	 * @return array<string, string>
	 */
	public static function statuses(): array {
		return array(
			'new'       => __( 'Novo', 'resgata-wp' ),
			'reviewing' => __( 'Em análise', 'resgata-wp' ),
			'interview' => __( 'Entrevista', 'resgata-wp' ),
			'approved'  => __( 'Aprovado', 'resgata-wp' ),
			'declined'  => __( 'Recusado', 'resgata-wp' ),
		);
	}

	/**
	 * Registra o post type.
	 */
	public static function register_post_type(): void {
		$capability = Capabilities::ADOPTIONS;

		register_post_type(
			self::SLUG,
			array(
				'labels'              => array(
					'name'          => __( 'Pedidos de adoção', 'resgata-wp' ),
					'singular_name' => __( 'Pedido de adoção', 'resgata-wp' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => false,
				'supports'            => array( 'title' ),
				'map_meta_cap'        => false,
				'capabilities'        => array(
					'edit_post'              => $capability,
					'read_post'              => $capability,
					'delete_post'            => $capability,
					'edit_posts'             => $capability,
					'edit_others_posts'      => $capability,
					'publish_posts'          => $capability,
					'read_private_posts'     => $capability,
					'delete_posts'           => $capability,
					'delete_private_posts'   => $capability,
					'delete_published_posts' => $capability,
					'delete_others_posts'    => $capability,
					'edit_private_posts'     => $capability,
					'edit_published_posts'   => $capability,
					'create_posts'           => $capability,
				),
			)
		);
	}
}
