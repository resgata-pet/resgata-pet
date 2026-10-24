<?php
/**
 * Title: Página de contato
 * Slug: resgata-tema/pagina-contato
 * Categories: resgata-tema, contact
 * Keywords: contato, whatsapp, telefone, redes sociais
 * Block Types: core/post-content
 * Post Types: page
 * Description: Página completa de contato: chamada, WhatsApp em destaque, dados da protetora, redes sociais e Pix. Use com o modelo "Página sem título".
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
		<h1 class="wp-block-heading is-style-text-display"><?php esc_html_e( 'Fale com a gente', 'resgata-tema' ); ?><br><mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-accent-3-color"><?php esc_html_e( 'vamos conversar!', 'resgata-tema' ); ?></mark></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"resgata-needs-adoptions","fontSize":"large"} -->
		<p class="resgata-needs-adoptions has-large-font-size"><?php esc_html_e( 'Quer adotar, ajudar ou tirar uma dúvida? Escolha o jeito mais fácil para você. Respondemos assim que possível.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"resgata-without-adoptions","fontSize":"large"} -->
		<p class="resgata-without-adoptions has-large-font-size"><?php esc_html_e( 'Quer ajudar ou tirar uma dúvida? Escolha o jeito mais fácil para você. Respondemos assim que possível.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:resgata-pet/contact {"align":"wide"} /-->

<!-- wp:pattern {"slug":"resgata-tema/home-chamada"} /-->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
