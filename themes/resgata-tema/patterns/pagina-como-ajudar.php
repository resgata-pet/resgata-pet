<?php
/**
 * Title: Página "Como ajudar"
 * Slug: resgata-tema/pagina-como-ajudar
 * Categories: resgata-tema, call-to-action
 * Keywords: ajudar, doar, doação, pix, padrinho, adotar
 * Block Types: core/post-content
 * Post Types: page
 * Description: Página completa com as formas de ajudar: doação por Pix (com QR Code), apadrinhamento e adoção. Use com o modelo "Página sem título".
 * Viewport Width: 1400
 *
 * Marcação de blocos pura: os links dos botões vêm das ligações de blocos do
 * plugin Resgata Pet (`resgata-pet/link`), resolvidas a cada exibição.
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"align":"full","className":"is-style-resgata-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-resgata-hero">
	<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":1,"className":"is-style-text-display"} -->
		<h1 class="wp-block-heading is-style-text-display"><?php esc_html_e( 'Como ajudar', 'resgata-tema' ); ?><br><mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-accent-3-color"><?php esc_html_e( 'cada gesto conta!', 'resgata-tema' ); ?></mark></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"large"} -->
		<p class="has-large-font-size"><?php esc_html_e( 'Todo o trabalho de resgate depende de quem acredita nele. Você pode doar qualquer valor por Pix, ajudar todo mês como padrinho ou dar um lar a um dos nossos resgatados.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-resgata-heart"} -->
			<div class="wp-block-button is-style-resgata-heart"><a class="wp-block-button__link wp-element-button" href="#resgata-pix"><?php esc_html_e( 'Doar por Pix', 'resgata-tema' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"resgata-tema/home-atalhos"} /-->

<!-- wp:resgata-pet/pix-donation {"align":"wide"} /-->

<!-- wp:pattern {"slug":"resgata-tema/home-chamada"} /-->

<!-- wp:resgata-pet/impact {"align":"wide"} /-->

<!-- wp:pattern {"slug":"resgata-tema/home-animais"} /-->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
