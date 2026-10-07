<?php
/**
 * Renderização do bloco "Doação única por Pix".
 *
 * @package ResgataWP
 */

use ResgataWP\Modules\Settings\PixDonation;

defined( 'ABSPATH' ) || exit;

$rwp_html = PixDonation::render(
	get_block_wrapper_attributes(
		array(
			'class' => 'rwp-pix',
			'id'    => 'rwp-pix',
		)
	),
	true
);

if ( '' === $rwp_html && wp_is_serving_rest_request() ) {
	// Prévia no editor enquanto a chave Pix (ou a página "Como ajudar") não existe.
	$rwp_html = sprintf(
		'<div %s><p>%s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'rwp-pix rwp-pix--placeholder' ) ),
		esc_html__( 'Aqui aparece a doação por Pix quando a chave estiver cadastrada em Configurações, no painel Resgata.', 'resgata-wp' )
	);
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template escapa cada valor; os atributos do wrapper vêm escapados do core.
echo $rwp_html;
