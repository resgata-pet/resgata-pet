<?php
/**
 * Renderização do bloco "Números da protetora".
 *
 * @package ResgataPet
 */

use ResgataPet\Modules\Animals\Frontend\Impact;

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template escapa cada valor; os atributos do wrapper vêm escapados do core.
echo Impact::render( get_block_wrapper_attributes( array( 'class' => 'resgata-impact' ) ) );
