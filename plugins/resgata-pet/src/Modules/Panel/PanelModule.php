<?php
/**
 * Painel do Resgata Pet (/painel).
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Panel;

use ResgataPet\Core\Modules;
use ResgataPet\Modules\Panel\Rest\AdoptionsController;
use ResgataPet\Modules\Panel\Rest\AnimalsController;
use ResgataPet\Modules\Panel\Rest\BannersController;
use ResgataPet\Modules\Panel\Rest\DashboardController;
use ResgataPet\Modules\Panel\Rest\ModulesController;
use ResgataPet\Modules\Panel\Rest\SettingsController;
use ResgataPet\Modules\Panel\Rest\SponsorshipController;

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
	 * Registra as rotas da API do painel (`resgata-pet/v1`), só dos módulos ligados.
	 */
	public function register_rest_routes(): void {
		( new DashboardController() )->register_routes();
		( new SettingsController() )->register_routes();
		( new BannersController() )->register_routes();
		( new ModulesController() )->register_routes();

		if ( Modules::is_enabled( 'animals' ) ) {
			( new AnimalsController() )->register_routes();
		}

		if ( Modules::is_enabled( 'adoptions' ) ) {
			( new AdoptionsController() )->register_routes();
		}

		if ( Modules::is_enabled( 'sponsorship' ) ) {
			( new SponsorshipController() )->register_routes();
		}
	}
}
