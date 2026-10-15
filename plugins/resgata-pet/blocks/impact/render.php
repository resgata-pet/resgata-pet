<?php
/**
 * Renderização do bloco "Números da protetora".
 *
 * @package ResgataPet
 */

use ResgataPet\Core\Html;
use ResgataPet\Modules\Animals\Frontend\Impact;

defined( 'ABSPATH' ) || exit;

echo wp_kses( Impact::render( get_block_wrapper_attributes( array( 'class' => 'resgata-impact' ) ) ), Html::allowed() );
