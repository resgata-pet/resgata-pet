<?php
/**
 * Renderização do bloco "Planos de apadrinhamento".
 *
 * @package ResgataWP
 *
 * @var array<string, mixed> $attributes Atributos do bloco.
 */

use ResgataWP\Modules\Sponsorship\Blocks;

defined( 'ABSPATH' ) || exit;

$rwp_html = Blocks::render_plans(
	(bool) ( $attributes['showCounter'] ?? true ),
	get_block_wrapper_attributes(
		array(
			'class' => 'rwp-sponsorship',
			'id'    => 'rwp-apadrinhar',
		)
	)
);

if ( '' === $rwp_html && wp_is_serving_rest_request() ) {
	// Prévia no editor enquanto a Stripe não está configurada.
	$rwp_html = sprintf(
		'<div %s><p>%s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'rwp-sponsorship rwp-sponsorship--placeholder' ) ),
		esc_html__( 'Aqui aparecem os planos de apadrinhamento quando a Stripe estiver configurada (veja Padrinhos no painel Resgata).', 'resgata-wp' )
	);
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template escapa cada valor; os atributos do wrapper vêm escapados do core.
echo $rwp_html;
