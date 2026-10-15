<?php
/**
 * Renderização do bloco "Agradecimento ao padrinho".
 *
 * @package ResgataPet
 */

use ResgataPet\Core\Html;
use ResgataPet\Modules\Sponsorship\Blocks;

defined( 'ABSPATH' ) || exit;

echo wp_kses( Blocks::render_thanks( get_block_wrapper_attributes( array( 'class' => 'resgata-sponsorship-thanks' ) ) ), Html::allowed() );
