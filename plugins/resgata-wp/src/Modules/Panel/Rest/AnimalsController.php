<?php
/**
 * API do painel: animais.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Panel\Rest;

use ResgataWP\Modules\Animals\Animal;
use ResgataWP\Modules\Animals\AnimalWriter;
use ResgataWP\Modules\Animals\Meta;
use ResgataWP\Modules\Animals\Options;
use ResgataWP\Modules\Animals\PostType;
use ResgataWP\Modules\Animals\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Rotas `resgata-wp/v1/animals`: listar, ler, criar, atualizar e mover para a lixeira.
 */
final class AnimalsController extends BaseController {

	/**
	 * Quantidades permitidas de itens por página.
	 */
	private const PER_PAGE = array( 25, 50, 75, 100 );

	/**
	 * Status de post que o painel mostra e grava.
	 */
	private const POST_STATUSES = array( 'publish', 'draft' );

	/**
	 * Registra as rotas.
	 */
	public function register_routes(): void {
		$this->route(
			'/animals',
			\WP_REST_Server::READABLE,
			array( $this, 'list_items' ),
			static fn() => current_user_can( 'edit_rwp_animals' ),
			array(
				'page'     => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page' => array(
					'type'    => 'integer',
					'enum'    => self::PER_PAGE,
					'default' => 25,
				),
			) + $this->filter_args()
		);

		$this->route(
			'/animals',
			\WP_REST_Server::CREATABLE,
			array( $this, 'create_item' ),
			static fn() => current_user_can( 'edit_rwp_animals' ),
			$this->item_args( true )
		);

		$id_arg = array(
			'id' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
		);

		$this->route(
			'/animals/(?P<id>\d+)',
			\WP_REST_Server::READABLE,
			array( $this, 'get_item' ),
			fn( \WP_REST_Request $request ) => $this->can( 'edit_post', $request ),
			$id_arg
		);

		$this->route(
			'/animals/(?P<id>\d+)',
			\WP_REST_Server::EDITABLE,
			array( $this, 'update_item' ),
			fn( \WP_REST_Request $request ) => $this->can( 'edit_post', $request ),
			$id_arg + $this->item_args( false )
		);

		$this->route(
			'/animals/(?P<id>\d+)',
			\WP_REST_Server::DELETABLE,
			array( $this, 'trash_item' ),
			fn( \WP_REST_Request $request ) => $this->can( 'delete_post', $request ),
			$id_arg
		);
	}

	/**
	 * Filtros aceitos na listagem. Listas chegam separadas por vírgula (`status=reserved,adopted`).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function filter_args(): array {
		$list = static fn( array $allowed ) => array(
			'type'    => 'array',
			'default' => array(),
			'items'   => array(
				'type' => 'string',
				'enum' => $allowed,
			),
		);

		$date = array(
			'type'    => 'string',
			'pattern' => '^(\d{4}-\d{2}-\d{2})?$',
			'default' => '',
		);

		return array(
			'search'      => array(
				'type'      => 'string',
				'maxLength' => 100,
				'default'   => '',
			),
			'species'     => array(
				'type'     => 'array',
				'default'  => array(),
				'maxItems' => 20,
				'items'    => array(
					'type'    => 'string',
					'pattern' => '^[a-z0-9-]+$',
				),
			),
			'status'      => $list( array_keys( Options::statuses() ) ),
			'post_status' => $list( self::POST_STATUSES ),
			'sex'         => $list( array_keys( Options::sexes() ) ),
			'size'        => $list( array_keys( Options::sizes() ) ),
			'neutered'    => $list( array_keys( Options::yes_no() ) ),
			'rescue_from' => $date,
			'rescue_to'   => $date,
		);
	}

	/**
	 * Confere uma permissão sobre o animal da requisição.
	 *
	 * Para um ID que não existe ou não é um animal, nega: a resposta é a mesma
	 * de "sem permissão", para não revelar o que existe no site.
	 *
	 * @param string           $capability `edit_post` ou `delete_post`.
	 * @param \WP_REST_Request $request    Requisição.
	 */
	private function can( string $capability, \WP_REST_Request $request ): bool {
		$post = get_post( (int) $request['id'] );

		return $post && PostType::SLUG === $post->post_type && 'trash' !== $post->post_status && current_user_can( $capability, $post->ID );
	}

	/**
	 * Parâmetros de criação/edição.
	 *
	 * @param bool $creating Se é a rota de criação (nome e espécie obrigatórios).
	 * @return array<string, array<string, mixed>>
	 */
	private function item_args( bool $creating ): array {
		$date = array(
			'type'    => 'string',
			'pattern' => '^(\d{4}-\d{2}-\d{2})?$',
		);

		$choice = static fn( array $options ) => array(
			'type' => 'string',
			'enum' => array_merge( array( '' ), array_keys( $options ) ),
		);

		return array(
			'name'           => array(
				'type'      => 'string',
				'minLength' => 1,
				'maxLength' => 200,
				'required'  => $creating,
			),
			'species'        => array(
				'type'      => 'string',
				'minLength' => 1,
				'required'  => $creating,
			),
			'post_status'    => array(
				'type' => 'string',
				'enum' => self::POST_STATUSES,
			),
			'story'          => array( 'type' => 'string' ),
			'status'         => array(
				'type' => 'string',
				'enum' => array_keys( Options::statuses() ),
			),
			'sex'            => $choice( Options::sexes() ),
			'size'           => $choice( Options::sizes() ),
			'color'          => array(
				'type'      => 'string',
				'maxLength' => 100,
			),
			'weight'         => array(
				'type'    => array( 'number', 'null' ),
				'minimum' => 0,
				'maximum' => Meta::WEIGHT_MAX,
			),
			'tagline'        => array(
				'type'      => 'string',
				'maxLength' => Meta::TAGLINE_MAX,
			),
			'intro'          => array(
				'type'      => 'string',
				'maxLength' => Meta::INTRO_MAX,
			),
			'age_years'      => array(
				'type'    => array( 'integer', 'null' ),
				'minimum' => 0,
				'maximum' => 40,
			),
			'age_months'     => array(
				'type'    => array( 'integer', 'null' ),
				'minimum' => 0,
				'maximum' => 11,
			),
			'rescue_date'    => $date,
			'temperaments'   => array(
				'type'     => 'array',
				'maxItems' => 20,
				'items'    => array(
					'type'      => 'string',
					'maxLength' => 50,
				),
			),
			'neutered'       => $choice( Options::yes_no() ),
			'dewormed_date'  => $date,
			'fiv'            => $choice( Options::test_results() ),
			'felv'           => $choice( Options::test_results() ),
			'vet_checked'    => $choice( Options::yes_no() ),
			'vaccinated'     => $choice( Options::yes_no() ),
			'microchip'      => $choice( Options::yes_no() ),
			'vaccines'       => array(
				'type'     => 'array',
				'maxItems' => 50,
				'items'    => array(
					'type'       => 'object',
					'properties' => array(
						'name' => array(
							'type'      => 'string',
							'maxLength' => 100,
						),
						'date' => $date,
					),
				),
			),
			'featured_media' => array(
				'type'    => 'integer',
				'minimum' => 0,
			),
			'gallery'        => array(
				'type'     => 'array',
				'maxItems' => 60,
				'items'    => array(
					'type'       => 'object',
					'properties' => array(
						'id'   => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'type' => array(
							'type' => 'string',
							'enum' => array_keys( Options::photo_types() ),
						),
					),
				),
			),
		);
	}

	/**
	 * GET /animals: lista paginada, mais recentes primeiro.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 */
	public function list_items( \WP_REST_Request $request ): \WP_REST_Response {
		$post_statuses = array_values( array_intersect( self::POST_STATUSES, (array) $request['post_status'] ) );

		$query_args = array(
			'post_type'              => PostType::SLUG,
			'post_status'            => $post_statuses ? $post_statuses : self::POST_STATUSES,
			'posts_per_page'         => (int) $request['per_page'],
			'paged'                  => (int) $request['page'],
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'update_post_term_cache' => false,
		);

		$search = sanitize_text_field( (string) $request['search'] );
		if ( '' !== $search ) {
			$query_args['s']              = $search;
			$query_args['search_columns'] = array( 'post_title' );
		}

		if ( $request['species'] ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Filtro do painel; volume pequeno.
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => Taxonomies::SPECIES,
					'field'    => 'slug',
					'terms'    => array_map( 'sanitize_title', (array) $request['species'] ),
				),
			);
		}

		$meta_query = array();

		foreach (
			array(
				'status'   => Meta::STATUS,
				'sex'      => Meta::SEX,
				'size'     => Meta::SIZE,
				'neutered' => Meta::NEUTERED,
			) as $param => $meta_key
		) {
			if ( $request[ $param ] ) {
				$meta_query[] = array(
					'key'     => $meta_key,
					'value'   => array_map( 'sanitize_key', (array) $request[ $param ] ),
					'compare' => 'IN',
				);
			}
		}

		$from = Meta::sanitize_date( $request['rescue_from'] );
		$to   = Meta::sanitize_date( $request['rescue_to'] );

		if ( '' !== $from || '' !== $to ) {
			$meta_query[] = array(
				'key'     => Meta::RESCUE_DATE,
				'value'   => array( '' !== $from ? $from : '0001-01-01', '' !== $to ? $to : '9999-12-31' ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			);
		}

		if ( $meta_query ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filtro do painel; volume pequeno.
			$query_args['meta_query'] = $meta_query;
		}

		// Quem não pode editar animais de outras pessoas só vê os seus.
		if ( ! current_user_can( 'edit_others_rwp_animals' ) ) {
			$query_args['author'] = get_current_user_id();
		}

		$query = new \WP_Query( $query_args );
		$items = array();

		foreach ( $query->posts as $post ) {
			$animal  = new Animal( $post->ID );
			$items[] = array(
				'id'           => $post->ID,
				'name'         => $post->post_title,
				'post_status'  => $post->post_status,
				'status'       => $animal->status(),
				'status_label' => $animal->status_label(),
				'thumbnail'    => (string) get_the_post_thumbnail_url( $post->ID, 'thumbnail' ),
				'permalink'    => (string) get_permalink( $post->ID ),
			);
		}

		return new \WP_REST_Response(
			array(
				'items'       => $items,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
			)
		);
	}

	/**
	 * GET /animals/<id>: cadastro completo de um animal.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 */
	public function get_item( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( $this->prepare( (int) $request['id'] ) );
	}

	/**
	 * POST /animals: cria o animal como rascunho.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( \WP_REST_Request $request ) {
		$species = $this->validate_species( $request );
		if ( is_wp_error( $species ) ) {
			return $species;
		}

		$post_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'   => PostType::SLUG,
					'post_status' => 'draft',
					'post_title'  => sanitize_text_field( $request['name'] ),
				)
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$data = $this->writer_data( $request );
		// Todo animal novo nasce com uma situação definida.
		$data += array( 'status' => Options::STATUS_DEFAULT );

		AnimalWriter::save( $post_id, $data );

		return $this->finish( $post_id, $request, 201 );
	}

	/**
	 * PUT/PATCH /animals/<id>: atualiza os campos enviados.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_item( \WP_REST_Request $request ) {
		$post_id = (int) $request['id'];

		if ( $request->has_param( 'species' ) ) {
			$species = $this->validate_species( $request );
			if ( is_wp_error( $species ) ) {
				return $species;
			}
		}

		$update = array();

		if ( $request->has_param( 'name' ) ) {
			$update['post_title'] = sanitize_text_field( $request['name'] );
		}

		if ( $request->has_param( 'story' ) ) {
			// Só a formatação permitida em posts; scripts e afins são removidos.
			$update['post_content'] = wp_kses_post( $request['story'] );
		}

		if ( $update ) {
			$result = wp_update_post( wp_slash( array( 'ID' => $post_id ) + $update ), true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		AnimalWriter::save( $post_id, $this->writer_data( $request ) );

		return $this->finish( $post_id, $request, 200 );
	}

	/**
	 * DELETE /animals/<id>: move para a lixeira (não apaga em definitivo).
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function trash_item( \WP_REST_Request $request ) {
		$post_id = (int) $request['id'];

		if ( ! wp_trash_post( $post_id ) ) {
			return new \WP_Error( 'rwp_rest_trash_failed', __( 'Não foi possível excluir o animal. Tente de novo.', 'resgata-wp' ), array( 'status' => 500 ) );
		}

		return new \WP_REST_Response(
			array(
				'id'      => $post_id,
				'trashed' => true,
			)
		);
	}

	/**
	 * Confere se a espécie enviada existe.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return true|\WP_Error
	 */
	private function validate_species( \WP_REST_Request $request ) {
		if ( get_term_by( 'slug', sanitize_title( (string) $request['species'] ), Taxonomies::SPECIES ) ) {
			return true;
		}

		return new \WP_Error( 'rwp_rest_invalid_species', __( 'Escolha uma espécie da lista.', 'resgata-wp' ), array( 'status' => 400 ) );
	}

	/**
	 * Separa, da requisição, só os campos enviados que o AnimalWriter grava.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return array<string, mixed>
	 */
	private function writer_data( \WP_REST_Request $request ): array {
		$fields = array( 'species', 'status', 'sex', 'size', 'color', 'age_years', 'age_months', 'rescue_date', 'temperaments', 'neutered', 'dewormed_date', 'fiv', 'felv', 'vaccines', 'featured_media', 'gallery', 'tagline', 'intro', 'weight', 'vet_checked', 'vaccinated', 'microchip' );
		$data   = array();

		foreach ( $fields as $field ) {
			if ( $request->has_param( $field ) ) {
				$data[ $field ] = $request[ $field ];
			}
		}

		return $data;
	}

	/**
	 * Aplica a mudança de status do post (rascunho/publicado) e devolve o animal.
	 *
	 * @param int              $post_id ID do animal.
	 * @param \WP_REST_Request $request Requisição.
	 * @param int              $status  Código HTTP de sucesso.
	 * @return \WP_REST_Response|\WP_Error
	 */
	private function finish( int $post_id, \WP_REST_Request $request, int $status ) {
		$wanted = $request->has_param( 'post_status' ) ? (string) $request['post_status'] : '';

		if ( '' !== $wanted && get_post_status( $post_id ) !== $wanted ) {
			if ( 'publish' === $wanted && ! current_user_can( 'publish_rwp_animals' ) ) {
				return new \WP_Error( 'rwp_rest_cannot_publish', __( 'Você não tem permissão para publicar animais.', 'resgata-wp' ), array( 'status' => 403 ) );
			}

			$result = wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => $wanted,
				),
				true
			);

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return new \WP_REST_Response( $this->prepare( $post_id ), $status );
	}

	/**
	 * Dados de uma imagem para o painel.
	 *
	 * @param int $attachment_id ID do anexo.
	 * @return array{id: int, url: string}|null
	 */
	private function image( int $attachment_id ): ?array {
		$url = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : false;

		return $url ? array(
			'id'  => $attachment_id,
			'url' => $url,
		) : null;
	}

	/**
	 * Monta a resposta com o cadastro completo.
	 *
	 * @param int $post_id ID do animal.
	 * @return array<string, mixed>
	 */
	private function prepare( int $post_id ): array {
		$post    = get_post( $post_id );
		$animal  = new Animal( $post_id );
		$species = $animal->species();
		$age     = $animal->age();
		$gallery = array();

		foreach ( $animal->gallery() as $photo ) {
			$image = $this->image( $photo['id'] );
			if ( $image ) {
				$gallery[] = $image + array( 'type' => $photo['type'] );
			}
		}

		return array(
			'id'             => $post_id,
			'name'           => $post->post_title,
			'post_status'    => $post->post_status,
			'permalink'      => (string) get_permalink( $post_id ),
			// Com parágrafos, para o editor de texto do painel.
			'story'          => wpautop( $post->post_content ),
			'species'        => $species ? $species->slug : '',
			'status'         => $animal->status(),
			'sex'            => $animal->sex(),
			'size'           => $animal->size(),
			'color'          => $animal->color(),
			'weight'         => '' !== $animal->weight() ? (float) $animal->weight() : null,
			'tagline'        => $animal->tagline(),
			'intro'          => $animal->intro(),
			'age_years'      => $age ? $age['years'] : null,
			'age_months'     => $age ? $age['months'] : null,
			'rescue_date'    => $animal->rescue_date(),
			'temperaments'   => $animal->temperaments(),
			'neutered'       => $animal->neutered(),
			'dewormed_date'  => $animal->dewormed_date(),
			'fiv'            => $animal->fiv(),
			'felv'           => $animal->felv(),
			'vet_checked'    => $animal->vet_checked(),
			'vaccinated'     => $animal->vaccinated(),
			'microchip'      => $animal->microchip(),
			'vaccines'       => $animal->vaccines(),
			'featured_image' => $this->image( (int) get_post_thumbnail_id( $post_id ) ),
			'gallery'        => $gallery,
		);
	}
}
