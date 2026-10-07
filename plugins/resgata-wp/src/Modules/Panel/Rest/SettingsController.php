<?php
/**
 * API do painel: configurações da protetora.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Panel\Rest;

use ResgataWP\Core\Capabilities;
use ResgataWP\Modules\Settings\ContactPage;
use ResgataWP\Modules\Settings\HelpPage;
use ResgataWP\Modules\Settings\Organization;

defined( 'ABSPATH' ) || exit;

/**
 * Rotas `resgata-wp/v1/settings`: ler e salvar os dados da protetora.
 */
final class SettingsController extends BaseController {

	/**
	 * Registra as rotas.
	 */
	public function register_routes(): void {
		$permission = static fn() => current_user_can( Capabilities::SETTINGS );
		$args       = array();

		foreach ( Organization::fields() as $field => $max ) {
			$args[ $field ] = array(
				'type'      => 'string',
				'maxLength' => $max,
			);
		}

		$args['state']['enum']        = array_merge( array( '' ), array_keys( Organization::states() ) );
		$args['pix_key_type']['enum'] = array_merge( array( '' ), array_keys( Organization::pix_key_types() ) );

		$this->route( '/settings', \WP_REST_Server::READABLE, array( $this, 'get_settings' ), $permission );
		$this->route( '/settings', \WP_REST_Server::EDITABLE, array( $this, 'update_settings' ), $permission, $args );
	}

	/**
	 * GET /settings.
	 */
	public function get_settings(): \WP_REST_Response {
		return new \WP_REST_Response( $this->prepare( Organization::get() ) );
	}

	/**
	 * PUT/PATCH /settings: grava os campos enviados e garante a página de contato.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_settings( \WP_REST_Request $request ) {
		$changes = array();

		foreach ( array_keys( Organization::fields() ) as $field ) {
			if ( $request->has_param( $field ) ) {
				$changes[ $field ] = (string) $request[ $field ];
			}
		}

		foreach ( array( 'email', 'adoption_email' ) as $field ) {
			if ( isset( $changes[ $field ] ) && '' !== trim( $changes[ $field ] ) && ! is_email( trim( $changes[ $field ] ) ) ) {
				return new \WP_Error(
					'rwp_rest_invalid_email',
					__( 'Confira o e-mail informado: o formato não parece válido.', 'resgata-wp' ),
					array(
						'status' => 400,
						'field'  => $field,
					)
				);
			}
		}

		$data = Organization::update( $changes );

		// A primeira vez que os dados são salvos cria a página "Contato" do site.
		ContactPage::ensure();

		// Com a chave Pix cadastrada, nasce também a página "Como ajudar".
		HelpPage::ensure();

		return new \WP_REST_Response( $this->prepare( $data ) );
	}

	/**
	 * Resposta com os dados, as listas de opções e a página de contato.
	 *
	 * @param array<string, string> $data Dados da protetora.
	 * @return array<string, mixed>
	 */
	private function prepare( array $data ): array {
		return array(
			'settings'     => $data,
			'choices'      => array(
				'states'      => Organization::states(),
				'pixKeyTypes' => Organization::pix_key_types(),
			),
			'contact_page' => ContactPage::summary(),
			'help_page'    => HelpPage::summary(),
			'whatsapp_url' => Organization::whatsapp_url(),
		);
	}
}
