<?php
/**
 * Renderização do bloco "Banners".
 *
 * @package ResgataWP
 */

use ResgataWP\Modules\Settings\Banners;

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template escapa cada valor; os atributos do wrapper vêm escapados do core.
echo Banners::render( get_block_wrapper_attributes( array( 'class' => 'resgata-banners' ) ) );
