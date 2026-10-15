<?php
/**
 * Renderização do bloco "Banners".
 *
 * @package ResgataPet
 */

use ResgataPet\Core\Html;
use ResgataPet\Modules\Settings\Banners;

defined( 'ABSPATH' ) || exit;

echo wp_kses( Banners::render( get_block_wrapper_attributes( array( 'class' => 'resgata-banners' ) ) ), Html::allowed() );
