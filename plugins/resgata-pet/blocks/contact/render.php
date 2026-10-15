<?php
/**
 * Renderização do bloco "Contato da protetora".
 *
 * @package ResgataPet
 *
 * @var array<string, mixed> $attributes Atributos do bloco.
 */

use ResgataPet\Core\Html;
use ResgataPet\Modules\Settings\ContactBlock;

defined( 'ABSPATH' ) || exit;

$resgata_html = ContactBlock::render(
	(bool) ( $attributes['showPix'] ?? true ),
	get_block_wrapper_attributes( array( 'class' => empty( $attributes['compact'] ) ? 'resgata-contact' : 'resgata-contact-compact' ) ),
	! empty( $attributes['compact'] )
);

echo wp_kses( $resgata_html, Html::allowed() );
