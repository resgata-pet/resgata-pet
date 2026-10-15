<?php
/**
 * Renderização do bloco "Formulário de adoção".
 *
 * @package ResgataPet
 *
 * @var WP_Block $block Instância do bloco.
 */

use ResgataPet\Core\Html;
use ResgataPet\Modules\Adoptions\Form;

defined( 'ABSPATH' ) || exit;

$resgata_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();

$resgata_html = Form::render(
	$resgata_post_id,
	get_block_wrapper_attributes(
		array(
			'class' => 'resgata-adoption',
			'id'    => 'resgata-adocao',
		)
	)
);

if ( '' === $resgata_html && wp_is_serving_rest_request() ) {
	// Prévia no editor quando não há um animal disponível em contexto.
	$resgata_html = sprintf(
		'<div %s><p>%s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'resgata-adoption resgata-adoption--placeholder' ) ),
		esc_html__( 'Aqui aparece o formulário de adoção quando o animal está disponível.', 'resgata-pet' )
	);
}

echo wp_kses( $resgata_html, Html::allowed() );
