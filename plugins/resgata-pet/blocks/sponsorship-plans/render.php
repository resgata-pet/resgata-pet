<?php
/**
 * Renderização do bloco "Planos de apadrinhamento".
 *
 * @package ResgataPet
 *
 * @var array<string, mixed> $attributes Atributos do bloco.
 */

use ResgataPet\Core\Html;
use ResgataPet\Modules\Sponsorship\Blocks;

defined( 'ABSPATH' ) || exit;

$resgata_html = Blocks::render_plans(
	(bool) ( $attributes['showCounter'] ?? true ),
	get_block_wrapper_attributes(
		array(
			'class' => 'resgata-sponsorship',
			'id'    => 'resgata-apadrinhar',
		)
	)
);

if ( '' === $resgata_html && wp_is_serving_rest_request() ) {
	// Prévia no editor enquanto a Stripe não está configurada.
	$resgata_html = sprintf(
		'<div %s><p>%s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'resgata-sponsorship resgata-sponsorship--placeholder' ) ),
		esc_html__( 'Aqui aparecem os planos de apadrinhamento quando a Stripe estiver configurada (veja Padrinhos no painel Resgata).', 'resgata-pet' )
	);
}

echo wp_kses( $resgata_html, Html::allowed() );
