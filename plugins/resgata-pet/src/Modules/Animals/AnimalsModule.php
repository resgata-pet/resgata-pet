<?php
/**
 * Módulo de animais.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Animals;

use ResgataPet\Modules\Animals\CLI\SeedCommand;
use ResgataPet\Modules\Animals\Frontend\Blocks;
use ResgataPet\Modules\Animals\Frontend\Shortcode;
use ResgataPet\Modules\Animals\Frontend\Templates;

defined( 'ABSPATH' ) || exit;

/**
 * Liga as partes do módulo de animais aos hooks do WordPress.
 */
final class AnimalsModule {

	/**
	 * Registra os hooks do módulo.
	 */
	public function register(): void {
		add_action( 'init', array( PostType::class, 'register_post_type' ) );
		add_action( 'init', array( Taxonomies::class, 'register_taxonomies' ) );
		add_action( 'init', array( Meta::class, 'register_meta' ) );

		( new Blocks() )->register();
		( new Shortcode() )->register();
		( new Templates() )->register();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'resgata seed-animals', SeedCommand::class );
		}
	}
}
