<?php
/**
 * Title: Gatos em destaque
 * Slug: resgata-tema/gatos-em-destaque
 * Categories: resgata-tema, featured
 * Keywords: gatos, adoção, animais, destaque
 * Description: Três gatos disponíveis para adoção, com botão para ver todos os animais. Some do site com o módulo Animais desligado.
 * Viewport Width: 1400
 *
 * Marcação de blocos pura: os links dos botões vêm das ligações de blocos do
 * plugin Resgata Pet (`resgata-pet/link`), resolvidas a cada exibição.
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"align":"full","className":"resgata-needs-animals","backgroundColor":"accent-5","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull resgata-needs-animals has-accent-5-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Gatos em destaque', 'resgata-tema' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center"} -->
	<p class="has-text-align-center"><?php esc_html_e( 'Eles já passaram por muita coisa e agora só precisam de um lar. Conheça alguns dos gatos que esperam por você.', 'resgata-tema' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:resgata-pet/animals-list {"species":"gato","statuses":["available"],"perPage":3,"columns":3,"showFilters":false,"showPagination":false,"align":"wide"} /-->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"metadata":{"bindings":{"url":{"source":"resgata-pet/link","args":{"key":"animals"}}}}} -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Ver todos os animais', 'resgata-tema' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
