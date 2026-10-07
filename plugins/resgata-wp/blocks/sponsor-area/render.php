<?php
/**
 * Renderização do bloco "Área do padrinho".
 *
 * @package ResgataWP
 */

use ResgataWP\Modules\Sponsorship\Blocks;

defined( 'ABSPATH' ) || exit;

$rwp_html = Blocks::render_area(
	get_block_wrapper_attributes(
		array(
			'class' => 'rwp-sponsor-area',
			'id'    => 'rwp-padrinho',
		)
	)
);

if ( '' === $rwp_html && wp_is_serving_rest_request() ) {
	// Prévia no editor enquanto a Stripe não está configurada.
	$rwp_html = sprintf(
		'<div %s><p>%s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'rwp-sponsor-area rwp-sponsor-area--placeholder' ) ),
		esc_html__( 'Aqui aparece a área do padrinho quando a Stripe estiver configurada.', 'resgata-wp' )
	);
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template escapa cada valor; os atributos do wrapper vêm escapados do core.
echo $rwp_html;
