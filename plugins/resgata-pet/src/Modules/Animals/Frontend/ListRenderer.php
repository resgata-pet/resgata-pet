<?php
/**
 * Listagem pública de animais.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Animals\Frontend;

use ResgataPet\Core\TemplateLoader;
use ResgataPet\Modules\Animals\Meta;
use ResgataPet\Modules\Animals\Options;
use ResgataPet\Modules\Animals\PostType;
use ResgataPet\Modules\Animals\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Monta a consulta e o HTML da listagem (usada pelo bloco, pelo shortcode e pelo arquivo).
 */
final class ListRenderer {

	public const PARAM_SPECIES = 'resgata_especie';
	public const PARAM_SEX     = 'resgata_sexo';
	public const PARAM_SIZE    = 'resgata_porte';
	public const PARAM_PAGE    = 'resgata_pg';

	/**
	 * Valores padrão da listagem.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'species'         => '',
			'statuses'        => Options::default_public_statuses(),
			'per_page'        => 12,
			'columns'         => 3,
			'show_filters'    => true,
			'show_pagination' => true,
		);
	}

	/**
	 * Valida os argumentos da listagem.
	 *
	 * @param array<string, mixed> $args Argumentos recebidos.
	 * @return array<string, mixed>
	 */
	public static function normalize( array $args ): array {
		$args = wp_parse_args( $args, self::defaults() );

		$statuses = array_values( array_intersect( array_map( 'sanitize_key', (array) $args['statuses'] ), array_keys( Options::statuses() ) ) );

		return array(
			'species'         => sanitize_title( (string) $args['species'] ),
			'statuses'        => $statuses ? $statuses : Options::default_public_statuses(),
			'per_page'        => min( 48, max( 1, absint( $args['per_page'] ) ) ),
			'columns'         => min( 6, max( 1, absint( $args['columns'] ) ) ),
			'show_filters'    => (bool) $args['show_filters'],
			'show_pagination' => (bool) $args['show_pagination'],
		);
	}

	/**
	 * Lê um filtro público da URL.
	 *
	 * @param string $param Nome do parâmetro.
	 */
	private static function request_value( string $param ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filtro público de leitura; o valor é validado contra uma lista fechada.
		return isset( $_GET[ $param ] ) && is_scalar( $_GET[ $param ] ) ? sanitize_title( wp_unslash( (string) $_GET[ $param ] ) ) : '';
	}

	/**
	 * Renderiza a listagem.
	 *
	 * @param array<string, mixed> $args               Argumentos (ver `defaults()`).
	 * @param string               $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 */
	public static function render( array $args = array(), string $wrapper_attributes = '' ): string {
		$args    = self::normalize( $args );
		$species = Taxonomies::species_options();

		$selected = array(
			'species' => '',
			'sex'     => '',
			'size'    => '',
		);

		if ( $args['show_filters'] ) {
			$selected['species'] = self::request_value( self::PARAM_SPECIES );
			$selected['sex']     = Meta::sanitize_choice( self::request_value( self::PARAM_SEX ), Options::sexes() );
			$selected['size']    = Meta::sanitize_choice( self::request_value( self::PARAM_SIZE ), Options::sizes() );

			if ( ! isset( $species[ $selected['species'] ] ) ) {
				$selected['species'] = '';
			}
		}

		// Uma listagem fixada em uma espécie não deixa o visitante trocar de espécie.
		$species_slug = '' !== $args['species'] ? $args['species'] : $selected['species'];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Paginação pública de leitura.
		$page = $args['show_pagination'] && isset( $_GET[ self::PARAM_PAGE ] ) ? max( 1, absint( wp_unslash( $_GET[ self::PARAM_PAGE ] ) ) ) : 1;

		$meta_query = array(
			array(
				'key'     => Meta::STATUS,
				'value'   => $args['statuses'],
				'compare' => 'IN',
			),
		);

		if ( '' !== $selected['sex'] ) {
			$meta_query[] = array(
				'key'   => Meta::SEX,
				'value' => $selected['sex'],
			);
		}

		if ( '' !== $selected['size'] ) {
			$meta_query[] = array(
				'key'   => Meta::SIZE,
				'value' => $selected['size'],
			);
		}

		$query_args = array(
			'post_type'              => PostType::SLUG,
			'post_status'            => 'publish',
			'posts_per_page'         => $args['per_page'],
			'paged'                  => $page,
			'no_found_rows'          => ! $args['show_pagination'],
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filtro por situação; volume pequeno (animais de uma protetora).
			'meta_query'             => $meta_query,
		);

		if ( '' !== $species_slug ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => Taxonomies::SPECIES,
					'field'    => 'slug',
					'terms'    => $species_slug,
				),
			);
		}

		/**
		 * Filtra os argumentos da consulta da listagem de animais.
		 *
		 * @param array<string, mixed> $query_args Argumentos do WP_Query.
		 * @param array<string, mixed> $args       Argumentos da listagem.
		 */
		$query = new \WP_Query( apply_filters( 'resgata_animals_list_query_args', $query_args, $args ) );

		wp_enqueue_style( 'resgata-frontend' );

		if ( $args['show_filters'] ) {
			Blocks::enqueue_select_assets();
		}

		return TemplateLoader::render(
			'animals-list.php',
			array(
				'wrapper_attributes' => '' !== $wrapper_attributes ? $wrapper_attributes : 'class="resgata-animals"',
				'animal_ids'         => array_map( 'intval', wp_list_pluck( $query->posts, 'ID' ) ),
				'total'              => $args['show_pagination'] ? (int) $query->found_posts : count( $query->posts ),
				'columns'            => $args['columns'],
				'show_filters'       => $args['show_filters'],
				'filters'            => array(
					'species' => '' === $args['species'] ? $species : array(),
					'sex'     => Options::sexes(),
					'size'    => Options::sizes(),
				),
				'selected'           => $selected,
				'form_action'        => self::form_action(),
				'hidden_fields'      => self::hidden_fields(),
				'has_active_filters' => (bool) array_filter( $selected ),
				'pagination'         => $args['show_pagination'] ? self::pagination( $page, (int) $query->max_num_pages ) : '',
			)
		);
	}

	/**
	 * Parâmetros próprios da listagem.
	 *
	 * @return string[]
	 */
	private static function params(): array {
		return array( self::PARAM_SPECIES, self::PARAM_SEX, self::PARAM_SIZE, self::PARAM_PAGE );
	}

	/**
	 * URL da página atual sem os filtros (destino do formulário).
	 */
	private static function form_action(): string {
		return (string) strtok( remove_query_arg( self::params() ), '?' );
	}

	/**
	 * Outros parâmetros da URL atual que precisam ser preservados ao filtrar
	 * (ex.: `page_id` em sites sem links permanentes).
	 *
	 * @return array<string, string>
	 */
	private static function hidden_fields(): array {
		$query = wp_parse_url( remove_query_arg( self::params() ), PHP_URL_QUERY );
		$keep  = array();

		if ( ! $query ) {
			return $keep;
		}

		wp_parse_str( $query, $vars );

		foreach ( $vars as $key => $value ) {
			if ( is_scalar( $value ) ) {
				$keep[ sanitize_key( $key ) ] = sanitize_text_field( (string) $value );
			}
		}

		return $keep;
	}

	/**
	 * Links de paginação.
	 *
	 * @param int $current Página atual.
	 * @param int $total   Total de páginas.
	 */
	private static function pagination( int $current, int $total ): string {
		if ( $total < 2 ) {
			return '';
		}

		$big   = 999999;
		$links = paginate_links(
			array(
				'base'      => str_replace( (string) $big, '%#%', esc_url( add_query_arg( self::PARAM_PAGE, $big ) ) ),
				'format'    => '',
				'current'   => $current,
				'total'     => $total,
				'prev_text' => __( '← Anterior', 'resgata-pet' ),
				'next_text' => __( 'Próxima →', 'resgata-pet' ),
			)
		);

		return $links ? (string) $links : '';
	}
}
