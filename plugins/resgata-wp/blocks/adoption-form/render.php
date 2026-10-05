<?php
/**
 * Renderização do bloco "Formulário de adoção".
 *
 * @package ResgataWP
 *
 * @var WP_Block $block Instância do bloco.
 */

use ResgataWP\Modules\Adoptions\Form;

defined( 'ABSPATH' ) || exit;

$rwp_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();

$rwp_html = Form::render(
	$rwp_post_id,
	get_block_wrapper_attributes(
		array(
			'class' => 'rwp-adoption',
			'id'    => 'rwp-adocao',
		)
	)
);

if ( '' === $rwp_html && wp_is_serving_rest_request() ) {
	// Prévia no editor quando não há um animal disponível em contexto.
	$rwp_html = sprintf(
		'<div %s><p>%s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'rwp-adoption rwp-adoption--placeholder' ) ),
		esc_html__( 'Aqui aparece o formulário de adoção quando o animal está disponível.', 'resgata-wp' )
	);
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template escapa cada valor; os atributos do wrapper vêm escapados do core.
echo $rwp_html;
