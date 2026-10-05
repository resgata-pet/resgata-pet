<?php
/**
 * Plugin Name:       Resgata WP
 * Description:       Kit para protetoras independentes de animais: divulgação de animais para adoção, pedidos de adoção, apadrinhamento e prestação de contas.
 * Version:           0.2.0
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Author:            Resgata WP
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       resgata-wp
 * Domain Path:       /languages
 *
 * @package ResgataWP
 */

defined( 'ABSPATH' ) || exit;

define( 'RWP_VERSION', '0.2.0' );
define( 'RWP_DB_VERSION', 2 );
define( 'RWP_PLUGIN_FILE', __FILE__ );
define( 'RWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( is_readable( RWP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once RWP_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	// Autoload PSR-4 de reserva, para o plugin funcionar sem `composer install`.
	spl_autoload_register(
		static function ( string $class_name ): void {
			$prefix = 'ResgataWP\\';
			if ( ! str_starts_with( $class_name, $prefix ) ) {
				return;
			}
			$file = RWP_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

register_activation_hook( __FILE__, array( ResgataWP\Core\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( ResgataWP\Core\Deactivator::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( ResgataWP\Plugin::class, 'boot' ) );
