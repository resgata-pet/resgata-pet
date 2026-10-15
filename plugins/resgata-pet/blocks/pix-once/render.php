<?php
/**
 * Renderização do bloco "Doação única por Pix".
 *
 * @package ResgataPet
 */

use ResgataPet\Core\Html;
use ResgataPet\Modules\Settings\PixDonation;

defined( 'ABSPATH' ) || exit;

$resgata_html = PixDonation::render(
	get_block_wrapper_attributes(
		array(
			'class' => 'resgata-pix',
			'id'    => 'resgata-pix',
		)
	),
	true
);

if ( '' === $resgata_html && wp_is_serving_rest_request() ) {
	// Prévia no editor enquanto a chave Pix (ou a página "Como ajudar") não existe.
	$resgata_html = sprintf(
		'<div %s><p>%s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'resgata-pix resgata-pix--placeholder' ) ),
		esc_html__( 'Aqui aparece a doação por Pix quando a chave estiver cadastrada em Configurações, no painel Resgata.', 'resgata-pet' )
	);
}

echo wp_kses( $resgata_html, Html::allowed() );
