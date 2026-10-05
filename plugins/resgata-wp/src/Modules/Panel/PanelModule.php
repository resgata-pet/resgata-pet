<?php
/**
 * Painel do Resgata WP (/painel).
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Panel;

use ResgataWP\Core\Modules;
use ResgataWP\Modules\Panel\Rest\AnimalsController;

defined( 'ABSPATH' ) || exit;

/**
 * Liga o painel aos hooks do WordPress: endereço próprio, acesso e API.
 */
final class PanelModule {

	/**
	 * Registra os hooks do painel.
	 */
	public function register(): void {
		( new Router() )->register();
		( new Access() )->register();

		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Registra as rotas da API do painel (`resgata-wp/v1`), só dos módulos ligados.
	 */
	public function register_rest_routes(): void {
		if ( Modules::is_enabled( 'animals' ) ) {
			( new AnimalsController() )->register_routes();
		}
	}
}
