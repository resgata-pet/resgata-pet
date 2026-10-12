<?php
/**
 * Módulo de animais.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals;

use ResgataWP\Modules\Animals\CLI\SeedCommand;
use ResgataWP\Modules\Animals\Frontend\Blocks;
use ResgataWP\Modules\Animals\Frontend\Shortcode;
use ResgataWP\Modules\Animals\Frontend\Templates;

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
