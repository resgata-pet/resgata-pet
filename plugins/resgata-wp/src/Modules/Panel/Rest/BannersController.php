<?php
/**
 * API do painel: banners da página inicial.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Panel\Rest;

use ResgataWP\Core\Capabilities;
use ResgataWP\Modules\Settings\Banners;

defined( 'ABSPATH' ) || exit;

/**
 * Rotas `resgata-wp/v1/banners`: ler e salvar a lista de banners do site.
 */
final class BannersController extends BaseController {

	/**
	 * Registra as rotas.
	 */
	public function register_routes(): void {
		$permission = static fn() => current_user_can( Capabilities::SETTINGS );
		$properties = array(
			'image_id' => array(
				'type'    => 'integer',
				'minimum' => 0,
			),
			'active'   => array( 'type' => 'boolean' ),
		);

		foreach ( Banners::TEXT_FIELDS as $field => $max ) {
			$properties[ $field ] = array(
				'type'      => 'string',
				'maxLength' => $max,
			);
		}

		foreach ( Banners::URL_FIELDS as $field ) {
			$properties[ $field ] = array(
				'type'      => 'string',
				'maxLength' => Banners::URL_MAX,
			);
		}

		$this->route( '/banners', \WP_REST_Server::READABLE, array( $this, 'get_banners' ), $permission );
		$this->route(
			'/banners',
			\WP_REST_Server::EDITABLE,
			array( $this, 'update_banners' ),
			$permission,
			array(
				'banners' => array(
					'type'     => 'array',
					'maxItems' => Banners::MAX,
					'items'    => array(
						'type'       => 'object',
						'properties' => $properties,
					),
				),
			)
		);
	}

	/**
	 * GET /banners.
	 */
	public function get_banners(): \WP_REST_Response {
		return new \WP_REST_Response( $this->prepare( Banners::get() ) );
	}

	/**
	 * PUT/PATCH /banners: troca a lista inteira pela enviada.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_banners( \WP_REST_Request $request ) {
		if ( ! $request->has_param( 'banners' ) ) {
			return $this->get_banners();
		}

		$banners = (array) $request['banners'];

		foreach ( $banners as $banner ) {
			foreach ( Banners::URL_FIELDS as $field ) {
				$url = is_array( $banner ) && isset( $banner[ $field ] ) ? trim( (string) $banner[ $field ] ) : '';

				if ( '' !== $url && '' === Banners::sanitize_url( $url ) ) {
					return new \WP_Error(
						'resgata_rest_invalid_url',
						__( 'Confira o link do botão: use um endereço completo (https://...) ou um caminho do site, como /animais/.', 'resgata-wp' ),
						array( 'status' => 400 )
					);
				}
			}
		}

		return new \WP_REST_Response( $this->prepare( Banners::update( $banners ) ) );
	}

	/**
	 * Resposta com os banners, a imagem de cada um e o limite.
	 *
	 * @param array<int, array<string, mixed>> $banners Banners.
	 * @return array<string, mixed>
	 */
	private function prepare( array $banners ): array {
		foreach ( $banners as $index => $banner ) {
			$url = $banner['image_id'] ? wp_get_attachment_image_url( $banner['image_id'], 'medium_large' ) : false;

			$banners[ $index ]['image'] = $url ? array(
				'id'  => $banner['image_id'],
				'url' => $url,
			) : null;
		}

		return array(
			'banners' => $banners,
			'max'     => Banners::MAX,
		);
	}
}
