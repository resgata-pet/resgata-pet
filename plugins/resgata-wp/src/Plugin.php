<?php
/**
 * Bootstrap do plugin.
 *
 * @package ResgataWP
 */

namespace ResgataWP;

use ResgataWP\Core\Modules;
use ResgataWP\Core\Schema;
use ResgataWP\Modules\Adoptions\AdoptionsModule;
use ResgataWP\Modules\Animals\AnimalsModule;
use ResgataWP\Modules\Panel\PanelModule;
use ResgataWP\Modules\Settings\SettingsModule;
use ResgataWP\Modules\Sponsorship\SponsorshipModule;

defined( 'ABSPATH' ) || exit;

/**
 * Carrega os módulos ativos do plugin.
 */
final class Plugin {

	/**
	 * Indica se o plugin já foi inicializado.
	 *
	 * @var bool
	 */
	private static bool $booted = false;

	/**
	 * Inicializa o plugin (hook `plugins_loaded`).
	 */
	public static function boot(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		Schema::maybe_upgrade();

		// Configurações é o único módulo que não pode ser desligado.
		( new SettingsModule() )->register();

		// O painel (/painel) também está sempre ligado; cada módulo acrescenta as suas rotas.
		( new PanelModule() )->register();

		if ( Modules::is_enabled( 'animals' ) ) {
			( new AnimalsModule() )->register();

			// Os pedidos de adoção são sempre de um animal: dependem do módulo Animais.
			if ( Modules::is_enabled( 'adoptions' ) ) {
				( new AdoptionsModule() )->register();
			}
		}

		// Modules::is_enabled() já descarta o apadrinhamento quando a biblioteca da Stripe não está instalada.
		if ( Modules::is_enabled( 'sponsorship' ) ) {
			( new SponsorshipModule() )->register();
		}

		/**
		 * Disparado depois que os módulos do Resgata WP foram carregados.
		 */
		do_action( 'rwp_loaded' );
	}
}
