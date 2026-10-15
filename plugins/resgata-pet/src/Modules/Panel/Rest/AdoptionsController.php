<?php
/**
 * API do painel: pedidos de adoção.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Panel\Rest;

use ResgataPet\Core\Capabilities;
use ResgataPet\Modules\Adoptions\PostType;
use ResgataPet\Modules\Adoptions\Questionnaire;
use ResgataPet\Modules\Animals\Animal;
use ResgataPet\Modules\Animals\Options as AnimalOptions;
use ResgataPet\Modules\Animals\PostType as AnimalPostType;
use ResgataPet\Modules\Settings\Organization;

defined( 'ABSPATH' ) || exit;

/**
 * Rotas `resgata-pet/v1/adoptions`: fila interna de pedidos de adoção.
 *
 * Os pedidos têm dados pessoais: todas as rotas exigem `resgata_manage_adoptions`
 * (Administrador e Protetor admin). Pedidos entram só pelo formulário do site.
 */
final class AdoptionsController extends BaseController {

	private const PER_PAGE = array( 25, 50, 75, 100 );

	/**
	 * Registra as rotas.
	 */
	public function register_routes(): void {
		$permission      = static fn() => current_user_can( Capabilities::ADOPTIONS );
		$item_permission = fn( \WP_REST_Request $request ) => current_user_can( Capabilities::ADOPTIONS ) && null !== $this->find( (int) $request['id'] );
		$statuses        = array_keys( PostType::statuses() );

		$id_arg = array(
			'id' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
		);

		$this->route(
			'/adoptions',
			\WP_REST_Server::READABLE,
			array( $this, 'list_items' ),
			$permission,
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
				'status'   => array(
					'type'    => 'array',
					'default' => array(),
					'items'   => array(
						'type' => 'string',
						'enum' => $statuses,
					),
				),
				'animal'   => array(
					'type'    => 'integer',
					'minimum' => 0,
					'default' => 0,
				),
				'search'   => array(
					'type'      => 'string',
					'maxLength' => 100,
					'default'   => '',
				),
			)
		);

		$this->route( '/adoptions/(?P<id>\d+)', \WP_REST_Server::READABLE, array( $this, 'get_item' ), $item_permission, $id_arg );

		$this->route(
			'/adoptions/(?P<id>\d+)',
			\WP_REST_Server::EDITABLE,
			array( $this, 'update_item' ),
			$item_permission,
			$id_arg + array(
				'status' => array(
					'type' => 'string',
					'enum' => $statuses,
				),
				'notes'  => array(
					'type'      => 'string',
					'maxLength' => 5000,
				),
			)
		);

		$this->route( '/adoptions/(?P<id>\d+)', \WP_REST_Server::DELETABLE, array( $this, 'delete_item' ), $item_permission, $id_arg );
	}

	/**
	 * Localiza um pedido pelo ID.
	 *
	 * @param int $id ID do post.
	 */
	private function find( int $id ): ?\WP_Post {
		$post = get_post( $id );

		return $post && PostType::SLUG === $post->post_type && 'trash' !== $post->post_status ? $post : null;
	}

	/**
	 * GET /adoptions: lista paginada, mais recentes primeiro.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 */
	public function list_items( \WP_REST_Request $request ): \WP_REST_Response {
		$meta_query = array();

		if ( $request['status'] ) {
			$meta_query[] = array(
				'key'     => PostType::META_STATUS,
				'value'   => array_map( 'sanitize_key', (array) $request['status'] ),
				'compare' => 'IN',
			);
		}

		if ( $request['animal'] ) {
			$meta_query[] = array(
				'key'   => PostType::META_ANIMAL,
				'value' => (int) $request['animal'],
			);
		}

		$query_args = array(
			'post_type'              => PostType::SLUG,
			'post_status'            => 'private',
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

		if ( $meta_query ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filtro do painel; volume pequeno.
			$query_args['meta_query'] = $meta_query;
		}

		$query = new \WP_Query( $query_args );
		$items = array();

		foreach ( $query->posts as $post ) {
			$items[] = $this->summary( $post );
		}

		return new \WP_REST_Response(
			array(
				'items'       => $items,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
				'new_count'   => $this->count_new(),
			)
		);
	}

	/**
	 * GET /adoptions/<id>.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 */
	public function get_item( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( $this->prepare( (int) $request['id'] ) );
	}

	/**
	 * PUT/PATCH /adoptions/<id>: muda a situação e/ou as anotações internas.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 */
	public function update_item( \WP_REST_Request $request ): \WP_REST_Response {
		$id = (int) $request['id'];

		if ( $request->has_param( 'status' ) ) {
			update_post_meta( $id, PostType::META_STATUS, sanitize_key( $request['status'] ) );
		}

		if ( $request->has_param( 'notes' ) ) {
			update_post_meta( $id, PostType::META_NOTES, wp_slash( sanitize_textarea_field( $request['notes'] ) ) );
		}

		return new \WP_REST_Response( $this->prepare( $id ) );
	}

	/**
	 * DELETE /adoptions/<id>: apaga o pedido em definitivo (são dados pessoais; não há lixeira).
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( \WP_REST_Request $request ) {
		$id = (int) $request['id'];

		if ( ! wp_delete_post( $id, true ) ) {
			return new \WP_Error( 'resgata_rest_delete_failed', __( 'Não foi possível excluir o pedido. Tente de novo.', 'resgata-pet' ), array( 'status' => 500 ) );
		}

		return new \WP_REST_Response(
			array(
				'id'      => $id,
				'deleted' => true,
			)
		);
	}

	/**
	 * Quantidade de pedidos novos (ainda não analisados).
	 */
	private function count_new(): int {
		$query = new \WP_Query(
			array(
				'post_type'      => PostType::SLUG,
				'post_status'    => 'private',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Contador do painel; volume pequeno.
				'meta_key'       => PostType::META_STATUS,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'     => PostType::STATUS_DEFAULT,
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Situação gravada do pedido, validada.
	 *
	 * @param int $id ID do pedido.
	 */
	private function status( int $id ): string {
		$status = (string) get_post_meta( $id, PostType::META_STATUS, true );

		return isset( PostType::statuses()[ $status ] ) ? $status : PostType::STATUS_DEFAULT;
	}

	/**
	 * Dados do animal do pedido, para o painel.
	 *
	 * @param int $id ID do pedido.
	 * @return array<string, mixed>|null
	 */
	private function animal( int $id ): ?array {
		$animal_id = (int) get_post_meta( $id, PostType::META_ANIMAL, true );
		$post      = $animal_id ? get_post( $animal_id ) : null;

		if ( ! $post || AnimalPostType::SLUG !== $post->post_type ) {
			return null;
		}

		$status = ( new Animal( $animal_id ) )->status();

		return array(
			'id'           => $animal_id,
			'name'         => $post->post_title,
			'thumbnail'    => (string) get_the_post_thumbnail_url( $animal_id, 'thumbnail' ),
			'status'       => $status,
			'status_label' => AnimalOptions::statuses()[ $status ],
			'trashed'      => 'trash' === $post->post_status,
		);
	}

	/**
	 * Converte uma data gravada no fuso do site para ISO 8601 com o fuso, para o navegador mostrar a hora certa.
	 *
	 * @param string $local_date Data no formato do banco (fuso do site).
	 */
	private function iso_date( string $local_date ): string {
		$date = date_create_immutable( $local_date, wp_timezone() );

		return $date ? $date->format( DATE_ATOM ) : '';
	}

	/**
	 * Resumo do pedido para a listagem.
	 *
	 * @param \WP_Post $post Pedido.
	 * @return array<string, mixed>
	 */
	private function summary( \WP_Post $post ): array {
		return array(
			'id'     => $post->ID,
			'name'   => $post->post_title,
			'status' => $this->status( $post->ID ),
			'date'   => $this->iso_date( $post->post_date ),
			'animal' => $this->animal( $post->ID ),
		);
	}

	/**
	 * Pedido completo.
	 *
	 * @param int $id ID do pedido.
	 * @return array<string, mixed>
	 */
	private function prepare( int $id ): array {
		$post    = get_post( $id );
		$answers = get_post_meta( $id, PostType::META_ANSWERS, true );
		$answers = is_array( $answers ) ? $answers : array();
		$consent = get_post_meta( $id, PostType::META_CONSENT, true );
		$consent = is_array( $consent ) ? $consent : array();
		$phone   = isset( $answers['whatsapp'] ) && is_scalar( $answers['whatsapp'] ) ? (string) preg_replace( '/\D+/', '', (string) $answers['whatsapp'] ) : '';
		$email   = isset( $answers['email'] ) && is_scalar( $answers['email'] ) ? sanitize_email( (string) $answers['email'] ) : '';

		return $this->summary( $post ) + array(
			'answers'      => Questionnaire::readable( $answers ),
			'whatsapp'     => Organization::format_phone( $phone ),
			// Conversa com quem pediu a adoção (número dela, não o da protetora).
			'whatsapp_url' => '' !== $phone ? 'https://wa.me/' . ( strlen( $phone ) <= 11 ? '55' . $phone : $phone ) : '',
			'email'        => $email,
			'notes'        => (string) get_post_meta( $id, PostType::META_NOTES, true ),
			'consent_at'   => isset( $consent['at'] ) ? $this->iso_date( (string) $consent['at'] ) : '',
			'consent_text' => isset( $consent['text'] ) ? (string) $consent['text'] : '',
		);
	}
}
