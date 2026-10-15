<?php
/**
 * Renderização do bloco "Área do padrinho".
 *
 * @package ResgataPet
 */

use ResgataPet\Modules\Sponsorship\Blocks;

defined( 'ABSPATH' ) || exit;

$resgata_html = Blocks::render_area(
	get_block_wrapper_attributes(
		array(
			'class' => 'resgata-sponsor-area',
			'id'    => 'resgata-padrinho',
		)
	)
);

if ( '' === $resgata_html && wp_is_serving_rest_request() ) {
	// Prévia no editor enquanto a Stripe não está configurada.
	$resgata_html = sprintf(
		'<div %s><p>%s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'resgata-sponsor-area resgata-sponsor-area--placeholder' ) ),
		esc_html__( 'Aqui aparece a área do padrinho quando a Stripe estiver configurada.', 'resgata-pet' )
	);
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template escapa cada valor; os atributos do wrapper vêm escapados do core.
echo $resgata_html;
