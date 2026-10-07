<?php
/**
 * Title: Cabeçalho com botão "Quero ajudar"
 * Slug: resgata-tema/cabecalho
 * Categories: header
 * Block Types: core/template-part/header
 * Description: Logotipo e nome do site, menu e o botão "Quero ajudar", que leva à página "Como ajudar".
 * Viewport Width: 1400
 *
 * Marcação de blocos pura: os links dos botões vêm das ligações de blocos do
 * plugin Resgata WP (`resgata-wp/link`), resolvidas a cada exibição.
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"align":"full","className":"is-style-resgata-header","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-resgata-header">
	<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
		<!-- wp:group {"style":{"spacing":{"blockGap":"0.75rem"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group">
			<!-- wp:site-logo {"width":44,"shouldSyncIcon":false} /-->

			<!-- wp:site-title {"level":0,"fontSize":"large"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group">
			<!-- wp:navigation {"overlayMenu":"mobile","fontSize":"small","layout":{"type":"flex","justifyContent":"right"}} /-->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-resgata-heart","metadata":{"bindings":{"url":{"source":"resgata-wp/link","args":{"key":"help"}}}}} -->
				<div class="wp-block-button is-style-resgata-heart"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Quero ajudar', 'resgata-tema' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
