<?php
/**
 * Bootstrap do plugin.
 *
 * @package ResgataWP
 */

namespace ResgataWP;

use ResgataWP\Core\Modules;
use ResgataWP\Core\Schema;
use ResgataWP\Modules\Animals\AnimalsModule;
use ResgataWP\Modules\Settings\SettingsModule;

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

		if ( Modules::is_enabled( 'animals' ) ) {
			( new AnimalsModule() )->register();
		}

		/**
		 * Disparado depois que os módulos do Resgata WP foram carregados.
		 */
		do_action( 'rwp_loaded' );
	}
}
