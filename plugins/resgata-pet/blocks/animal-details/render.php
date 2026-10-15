<?php
/**
 * Renderização do bloco "Ficha do animal".
 *
 * @package ResgataPet
 *
 * @var array<string, mixed> $attributes Atributos do bloco.
 * @var WP_Block             $block      Instância do bloco.
 */

use ResgataPet\Modules\Animals\Frontend\DetailsRenderer;

defined( 'ABSPATH' ) || exit;

$resgata_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();

$resgata_html = DetailsRenderer::render(
	$resgata_post_id,
	(string) ( $attributes['section'] ?? 'all' ),
	get_block_wrapper_attributes( array( 'class' => 'resgata-animal' ) )
);

if ( '' === $resgata_html && wp_is_serving_rest_request() ) {
	// Prévia no editor quando não há um animal em contexto (ex.: editando o modelo no Editor do Site).
	$resgata_html = sprintf(
		'<div %s><p>%s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'resgata-animal resgata-animal--placeholder' ) ),
		esc_html__( 'Aqui aparece a página do animal (fotos, resumo, história e saúde) quando alguém abre um animal no site.', 'resgata-pet' )
	);
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template escapa cada valor; os atributos do wrapper vêm escapados do core.
echo $resgata_html;
