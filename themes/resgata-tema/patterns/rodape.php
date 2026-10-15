<?php
/**
 * Title: Rodapé com contato e links
 * Slug: resgata-tema/rodape
 * Categories: footer
 * Block Types: core/template-part/footer
 * Description: Nome do site e frase da protetora, links das páginas principais, formas de ajudar, contato com redes sociais e linha de direitos.
 * Viewport Width: 1400
 *
 * Marcação de blocos pura: os links dos botões vêm das ligações de blocos do
 * plugin Resgata Pet (`resgata-pet/link`), resolvidas a cada exibição.
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"align":"full","className":"is-style-resgata-footer","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-resgata-footer">
	<!-- wp:group {"align":"wide","layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"13rem"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:group {"style":{"spacing":{"blockGap":"0.75rem"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
			<div class="wp-block-group">
				<!-- wp:site-logo {"width":44,"shouldSyncIcon":false} /-->

				<!-- wp:site-title {"level":0,"fontSize":"large"} /-->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"resgata-pet/organization","args":{"key":"tagline"}}}},"textColor":"accent-4"} -->
			<p class="has-accent-4-color has-text-color"></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"fontSize":"medium"} -->
			<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'O site', 'resgata-tema' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:resgata-pet/links {"group":"site"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"fontSize":"medium"} -->
			<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Ajude', 'resgata-tema' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:resgata-pet/links {"group":"help"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"fontSize":"medium"} -->
			<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Fale com a gente', 'resgata-tema' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:resgata-pet/contact {"showPix":false,"compact":true} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"wide","className":"is-style-resgata-footer-bottom","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide is-style-resgata-footer-bottom">
		<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"resgata-pet/organization","args":{"key":"copyright"}}}},"textColor":"accent-4","fontSize":"small"} -->
		<p class="has-accent-4-color has-text-color has-small-font-size"></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
