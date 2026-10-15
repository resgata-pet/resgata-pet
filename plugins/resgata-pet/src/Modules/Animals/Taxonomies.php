<?php
/**
 * Taxonomias dos animais.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Animals;

defined( 'ABSPATH' ) || exit;

/**
 * Registra espécie e temperamento.
 */
final class Taxonomies {

	public const SPECIES     = 'resgata_species';
	public const TEMPERAMENT = 'resgata_temperament';

	/**
	 * Registra as taxonomias.
	 */
	public static function register_taxonomies(): void {
		$caps = array(
			'manage_terms' => 'manage_resgata_animal_terms',
			'edit_terms'   => 'manage_resgata_animal_terms',
			'delete_terms' => 'manage_resgata_animal_terms',
			'assign_terms' => 'edit_resgata_animals',
		);

		// Espécie e temperamento são escolhidos na ficha do animal, no painel: sem telas no wp-admin.
		register_taxonomy(
			self::SPECIES,
			PostType::SLUG,
			array(
				'labels'             => array(
					'name'          => __( 'Espécies', 'resgata-pet' ),
					'singular_name' => __( 'Espécie', 'resgata-pet' ),
					'menu_name'     => __( 'Espécies', 'resgata-pet' ),
					'all_items'     => __( 'Todas as espécies', 'resgata-pet' ),
					'edit_item'     => __( 'Editar espécie', 'resgata-pet' ),
					'add_new_item'  => __( 'Adicionar espécie', 'resgata-pet' ),
					'search_items'  => __( 'Buscar espécies', 'resgata-pet' ),
					'not_found'     => __( 'Nenhuma espécie encontrada.', 'resgata-pet' ),
				),
				'hierarchical'       => true,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_nav_menus'  => false,
				'show_in_rest'       => false,
				'rewrite'            => false,
				'capabilities'       => $caps,
			)
		);

		register_taxonomy(
			self::TEMPERAMENT,
			PostType::SLUG,
			array(
				'labels'             => array(
					'name'                       => __( 'Temperamento', 'resgata-pet' ),
					'singular_name'              => __( 'Temperamento', 'resgata-pet' ),
					'menu_name'                  => __( 'Temperamentos', 'resgata-pet' ),
					'all_items'                  => __( 'Todos os temperamentos', 'resgata-pet' ),
					'edit_item'                  => __( 'Editar temperamento', 'resgata-pet' ),
					'add_new_item'               => __( 'Adicionar temperamento', 'resgata-pet' ),
					'search_items'               => __( 'Buscar temperamentos', 'resgata-pet' ),
					'separate_items_with_commas' => __( 'Separe com vírgulas. Ex.: dócil, brincalhão, tímido', 'resgata-pet' ),
					'add_or_remove_items'        => __( 'Adicionar ou remover', 'resgata-pet' ),
					'choose_from_most_used'      => __( 'Escolher entre os mais usados', 'resgata-pet' ),
					'not_found'                  => __( 'Nenhum temperamento encontrado.', 'resgata-pet' ),
				),
				'hierarchical'       => false,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
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

		wp_insert_term( __( 'Gato', 'resgata-pet' ), self::SPECIES, array( 'slug' => 'gato' ) );
		wp_insert_term( __( 'Cão', 'resgata-pet' ), self::SPECIES, array( 'slug' => 'cao' ) );
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
