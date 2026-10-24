<?php
/**
 * API do painel: módulos ligados no site.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Panel\Rest;

use ResgataPet\Core\Capabilities;
use ResgataPet\Core\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * Rotas `resgata-pet/v1/modules`: ver e escolher o que o site oferece (animais, adoção, apadrinhamento).
 */
final class ModulesController extends BaseController {

	/**
	 * Registra as rotas.
	 */
	public function register_routes(): void {
		$permission = static fn() => current_user_can( Capabilities::SETTINGS );

		$this->route( '/modules', \WP_REST_Server::READABLE, array( $this, 'get_modules' ), $permission );
		$this->route(
			'/modules',
			\WP_REST_Server::EDITABLE,
			array( $this, 'update_modules' ),
			$permission,
			array(
				'modules' => array(
					'type'        => 'array',
					'uniqueItems' => true,
					'items'       => array(
						'type' => 'string',
						'enum' => Modules::choices(),
					),
				),
			)
		);
	}

	/**
	 * GET /modules.
	 */
	public function get_modules(): \WP_REST_Response {
		return new \WP_REST_Response( $this->prepare() );
	}

	/**
	 * PUT/PATCH /modules: troca a lista de módulos ligados pela enviada.
	 *
	 * Sem `modules` no corpo, nada muda. Desligar um módulo não apaga os dados dele.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 */
	public function update_modules( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $request->has_param( 'modules' ) ) {
			// A troca agenda a recriação das regras de URL (SettingsModule::schedule_rewrite_flush).
			update_option( Modules::OPTION, Modules::sanitize( (array) $request['modules'] ) );
		}

		return new \WP_REST_Response( $this->prepare() );
	}

	/**
	 * Lista dos módulos, com o que está marcado.
	 *
	 * @return array{modules: array<int, array<string, mixed>>}
	 */
	private function prepare(): array {
		$chosen  = Modules::chosen();
		$modules = array();

		foreach ( Modules::all() as $slug => $module ) {
			$modules[] = array(
				'slug'        => $slug,
				'label'       => $module['label'],
				'description' => $module['description'],
				'available'   => $module['available'],
				'requires'    => $module['requires'],
				'enabled'     => in_array( $slug, $chosen, true ),
			);
		}

		return array( 'modules' => $modules );
	}
}
