<?php
/**
 * Resgata Tema: somente aparência. Toda a funcionalidade fica no plugin Resgata WP.
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;

/**
 * Avisa o plugin Resgata WP que este tema cuida do visual dos blocos dele.
 */
function resgata_tema_setup(): void {
	add_theme_support( 'resgata-wp' );
}
add_action( 'after_setup_theme', 'resgata_tema_setup' );

/**
 * Estilos dos blocos do Resgata WP (site e editor).
 */
function resgata_tema_block_assets(): void {
	wp_enqueue_style(
		'resgata-tema-resgata-wp',
		get_theme_file_uri( 'assets/css/resgata-wp.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'enqueue_block_assets', 'resgata_tema_block_assets' );

/**
 * Categoria dos padrões do tema no inseridor.
 */
function resgata_tema_pattern_categories(): void {
	register_block_pattern_category(
		'resgata-tema',
		array(
			'label'       => __( 'Resgata', 'resgata-tema' ),
			'description' => __( 'Seções prontas para o site da protetora.', 'resgata-tema' ),
		)
	);
}
add_action( 'init', 'resgata_tema_pattern_categories' );
