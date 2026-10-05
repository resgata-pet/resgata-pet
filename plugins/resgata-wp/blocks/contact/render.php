<?php
/**
 * Renderização do bloco "Contato da protetora".
 *
 * @package ResgataWP
 *
 * @var array<string, mixed> $attributes Atributos do bloco.
 */

use ResgataWP\Modules\Settings\ContactBlock;

defined( 'ABSPATH' ) || exit;

$rwp_html = ContactBlock::render(
	(bool) ( $attributes['showPix'] ?? true ),
	get_block_wrapper_attributes( array( 'class' => 'rwp-contact' ) )
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template escapa cada valor; os atributos do wrapper vêm escapados do core.
echo $rwp_html;
