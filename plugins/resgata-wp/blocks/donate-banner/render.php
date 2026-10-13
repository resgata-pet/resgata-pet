<?php
/**
 * Renderização do bloco "Banner de doação".
 *
 * @package ResgataWP
 */

use ResgataWP\Modules\Settings\PixDonation;

defined( 'ABSPATH' ) || exit;

$resgata_html = PixDonation::render_banner( get_block_wrapper_attributes( array( 'class' => 'resgata-donate' ) ) );

if ( '' === $resgata_html && wp_is_serving_rest_request() ) {
	// Prévia no editor enquanto a chave Pix (ou a página "Como ajudar") não existe.
	$resgata_html = sprintf(
		'<div %s><p>%s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'resgata-donate resgata-donate--placeholder' ) ),
		esc_html__( 'Aqui aparece a doação por Pix quando a chave estiver cadastrada em Configurações, no painel Resgata.', 'resgata-wp' )
	);
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template escapa cada valor; os atributos do wrapper vêm escapados do core.
echo $resgata_html;
