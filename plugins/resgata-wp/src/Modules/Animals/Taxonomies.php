<?php
/**
 * Taxonomias dos animais.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals;

defined( 'ABSPATH' ) || exit;

/**
 * Registra espécie e temperamento.
 */
final class Taxonomies {

	public const SPECIES     = 'rwp_species';
	public const TEMPERAMENT = 'rwp_temperament';

	/**
	 * Registra as taxonomias.
	 */
	public static function register_taxonomies(): void {
		$caps = array(
			'manage_terms' => 'manage_rwp_animal_terms',
			'edit_terms'   => 'manage_rwp_animal_terms',
			'delete_terms' => 'manage_rwp_animal_terms',
			'assign_terms' => 'edit_rwp_animals',
		);

		// Espécie: escolhida em um campo simples na ficha do animal (sem painel próprio no editor).
		register_taxonomy(
			self::SPECIES,
			PostType::SLUG,
			array(
				'labels'             => array(
					'name'          => __( 'Espécies', 'resgata-wp' ),
					'singular_name' => __( 'Espécie', 'resgata-wp' ),
					'menu_name'     => __( 'Espécies', 'resgata-wp' ),
					'all_items'     => __( 'Todas as espécies', 'resgata-wp' ),
					'edit_item'     => __( 'Editar espécie', 'resgata-wp' ),
					'add_new_item'  => __( 'Adicionar espécie', 'resgata-wp' ),
					'search_items'  => __( 'Buscar espécies', 'resgata-wp' ),
					'not_found'     => __( 'Nenhuma espécie encontrada.', 'resgata-wp' ),
				),
				'hierarchical'       => true,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_admin_column'  => true,
				'show_in_nav_menus'  => false,
				'show_in_rest'       => false,
				'meta_box_cb'        => false,
				'rewrite'            => false,
				'capabilities'       => $caps,
			)
		);

		register_taxonomy(
			self::TEMPERAMENT,
			PostType::SLUG,
			array(
				'labels'             => array(
					'name'                       => __( 'Temperamento', 'resgata-wp' ),
					'singular_name'              => __( 'Temperamento', 'resgata-wp' ),
					'menu_name'                  => __( 'Temperamentos', 'resgata-wp' ),
					'all_items'                  => __( 'Todos os temperamentos', 'resgata-wp' ),
					'edit_item'                  => __( 'Editar temperamento', 'resgata-wp' ),
					'add_new_item'               => __( 'Adicionar temperamento', 'resgata-wp' ),
					'search_items'               => __( 'Buscar temperamentos', 'resgata-wp' ),
					'separate_items_with_commas' => __( 'Separe com vírgulas. Ex.: dócil, brincalhão, tímido', 'resgata-wp' ),
					'add_or_remove_items'        => __( 'Adicionar ou remover', 'resgata-wp' ),
					'choose_from_most_used'      => __( 'Escolher entre os mais usados', 'resgata-wp' ),
					'not_found'                  => __( 'Nenhum temperamento encontrado.', 'resgata-wp' ),
				),
				'hierarchical'       => false,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_admin_column'  => false,
				'show_in_nav_menus'  => false,
				'show_tagcloud'      => false,
				'show_in_rest'       => true,
				'rewrite'            => false,
				'capabilities'       => $caps,
			)
		);
	}

	/**
	 * Cria as espécies iniciais (Gato e Cão) se a taxonomia estiver vazia.
	 */
	public static function ensure_default_terms(): void {
		$existing = get_terms(
			array(
				'taxonomy'   => self::SPECIES,
				'hide_empty' => false,
				'fields'     => 'ids',
				'number'     => 1,
			)
		);

		if ( is_wp_error( $existing ) || $existing ) {
			return;
		}

		wp_insert_term( __( 'Gato', 'resgata-wp' ), self::SPECIES, array( 'slug' => 'gato' ) );
		wp_insert_term( __( 'Cão', 'resgata-wp' ), self::SPECIES, array( 'slug' => 'cao' ) );
	}

	/**
	 * Espécies cadastradas, no formato slug => nome.
	 *
	 * @return array<string, string>
	 */
	public static function species_options(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => self::SPECIES,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		return wp_list_pluck( $terms, 'name', 'slug' );
	}
}
