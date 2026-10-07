<?php
/**
 * Renderização do bloco "Ficha do animal".
 *
 * @package ResgataWP
 *
 * @var array<string, mixed> $attributes Atributos do bloco.
 * @var WP_Block             $block      Instância do bloco.
 */

use ResgataWP\Modules\Animals\Frontend\DetailsRenderer;

defined( 'ABSPATH' ) || exit;

$rwp_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();

$rwp_html = DetailsRenderer::render(
	$rwp_post_id,
	(string) ( $attributes['section'] ?? 'all' ),
	get_block_wrapper_attributes( array( 'class' => 'rwp-animal' ) )
);

if ( '' === $rwp_html && wp_is_serving_rest_request() ) {
	// Prévia no editor quando não há um animal em contexto (ex.: editando o modelo no Editor do Site).
	$rwp_html = sprintf(
		'<div %s><p>%s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'rwp-animal rwp-animal--placeholder' ) ),
		esc_html__( 'Aqui aparece a página do animal (fotos, resumo, história e saúde) quando alguém abre um animal no site.', 'resgata-wp' )
	);
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template escapa cada valor; os atributos do wrapper vêm escapados do core.
echo $rwp_html;
