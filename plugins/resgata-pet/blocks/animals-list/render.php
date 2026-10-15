<?php
/**
 * Renderização do bloco "Lista de animais".
 *
 * @package ResgataPet
 *
 * @var array<string, mixed> $attributes Atributos do bloco.
 */

use ResgataPet\Core\Html;
use ResgataPet\Modules\Animals\Frontend\ListRenderer;

defined( 'ABSPATH' ) || exit;

$resgata_html = ListRenderer::render(
	array(
		'species'         => $attributes['species'] ?? '',
		'statuses'        => $attributes['statuses'] ?? array(),
		'per_page'        => $attributes['perPage'] ?? 12,
		'columns'         => $attributes['columns'] ?? 3,
		'show_filters'    => $attributes['showFilters'] ?? true,
		'show_pagination' => $attributes['showPagination'] ?? true,
	),
	get_block_wrapper_attributes( array( 'class' => 'resgata-animals' ) )
);

echo wp_kses( $resgata_html, Html::allowed() );
