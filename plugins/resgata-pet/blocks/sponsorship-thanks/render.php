<?php
/**
 * Renderização do bloco "Agradecimento ao padrinho".
 *
 * @package ResgataPet
 */

use ResgataPet\Modules\Sponsorship\Blocks;

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template escapa cada valor; os atributos do wrapper vêm escapados do core.
echo Blocks::render_thanks( get_block_wrapper_attributes( array( 'class' => 'resgata-sponsorship-thanks' ) ) );
